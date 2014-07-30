<?php

namespace Phlite\Db\Driver;

use Phlite\Db\DbEngine;

class MySqlEngine extends DbEngine {
    function getCompiler() {
        return new MySqlCompiler();
    }

    function getConnection() {
        //Assert
        if(!strlen($user) || !strlen($host))
            return NULL;

        if (!($__db = mysqli_init()))
            return NULL;

        // Setup SSL if enabled
        if (isset($options['ssl']))
            $__db->ssl_set(
                    $options['ssl']['key'],
                    $options['ssl']['cert'],
                    $options['ssl']['ca'],
                    null, null);
        elseif(!$passwd)
            return NULL;

        $port = ini_get("mysqli.default_port");
        $socket = ini_get("mysqli.default_socket");
        $persistent = stripos($host, 'p:') === 0;
        if ($persistent)
            $host = substr($host, 2);
        if (strpos($host, ':') !== false) {
            list($host, $portspec) = explode(':', $host);
            // PHP may not honor the port number if connecting to 'localhost'
            if ($portspec && is_numeric($portspec)) {
                if (!strcasecmp($host, 'localhost'))
                    // XXX: Looks like PHP gethostbyname() is IPv4 only
                    $host = gethostbyname($host);
                $port = (int) $portspec;
            }
            elseif ($portspec) {
                $socket = $portspec;
            }
        }

        if ($persistent)
            $host = 'p:' . $host;

        // Connect
        if (!@$__db->real_connect($host, $user, $passwd, null, $port, $socket))
            return NULL;

        //Select the database, if any.
        if(isset($options['db'])) $__db->select_db($options['db']);

        //set desired encoding just in case mysql charset is not UTF-8 - Thanks to FreshMedia
        @$__db->query('SET NAMES "utf8"');
        @$__db->query('SET CHARACTER SET "utf8"');
        @$__db->query('SET COLLATION_CONNECTION=utf8_general_ci');
        $__db->set_charset('utf8');

        @db_set_variable('sql_mode', '');

        return $__db;
    }

    function prepare($sql) {
        return $this->getConnection()->prepare($sql);
    }
}


class MySqlCompiler extends SqlCompiler {

    static $operators = array(
        'exact' => '%1$s = %2$s',
        'contains' => array('self', '__contains'),
        'gt' => '%1$s > %2$s',
        'gte' => '%1$s >= %2$s',
        'lt' => '%1$s < %2$s',
        'lte' => '%1$s <= %2$s',
        'isnull' => '%1$s IS NULL',
        'like' => '%1$s LIKE %2$s',
        'in' => array('self', '__in'),
    );

    function __contains($a, $b) {
        # {%a} like %{$b}%
        return sprintf('%s LIKE %s', $a, $this->input("%$b%"));
    }

    function __in($a, $b) {
        if (is_array($b)) {
            $vals = array_map(array($this, 'input'), $b);
            $b = implode(', ', $vals);
        }
        else {
            $b = $this->input($b);
        }
        return sprintf('%s IN (%s)', $a, $b);
    }

    function compileJoin($tip, $model, $alias, $info) {
        $constraints = array();
        $join = ' JOIN ';
        if (isset($info['null']) && $info['null'])
            $join = ' LEFT'.$join;
        if (isset($this->joins[$tip]))
            $table = $this->joins[$tip]['alias'];
        else
            $table = $this->quote($model::$meta['table']);
        foreach ($info['constraint'] as $local => $foreign) {
            list($rmodel, $right) = explode('.', $foreign);
            // TODO: Support a constant constraint
            $constraints[] = sprintf("%s.%s = %s.%s",
                $table, $this->quote($local), $alias,
                $this->quote($right)
            );
        }
        return $join.$this->quote($rmodel::$meta['table'])
            .' '.$alias.' ON ('.implode(' AND ', $constraints).')';
    }

    function input($what) {
        if ($what instanceof QuerySet) {
            $q = $what->getQuery(array('nosort'=>true));
            $this->params += $q->params;
            return (string)$q;
        }
        else {
            $this->params[] = $what;
            return '?';
        }
    }

    function quote($what) {
        return "`$what`";
    }

    function compileCount($queryset) {
        $model = $queryset->model;
        $table = $model::$meta['table'];
        $where_pos = array();
        $where_neg = array();
        $joins = array();
        foreach ($queryset->constraints as $where) {
            $where_pos[] = $this->compileWhere($where, $model);
        }
        foreach ($queryset->exclusions as $where) {
            $where_neg[] = $this->compileWhere($where, $model);
        }

        $where = '';
        if ($where_pos || $where_neg) {
            $where = ' WHERE '.implode(' AND ', $where_pos)
                .implode(' AND NOT ', $where_neg);
        }
        $joins = $this->getJoins();
        $sql = 'SELECT COUNT(*) AS count FROM '.$this->quote($table).$joins.$where;
        $exec = new MysqlExecutor($sql, $this->params);
        $row = $exec->getArray();
        return $row['count'];
    }

    function compileSelect($queryset) {
        $model = $queryset->model;
        $where_pos = array();
        $where_neg = array();
        $joins = array();
        foreach ($queryset->constraints as $where) {
            $where_pos[] = $this->compileWhere($where, $model);
        }
        foreach ($queryset->exclusions as $where) {
            $where_neg[] = $this->compileWhere($where, $model);
        }

        $where = '';
        if ($where_pos || $where_neg) {
            $where = ' WHERE '.implode(' AND ', $where_pos)
                .implode(' AND NOT ', $where_neg);
        }

        $sort = '';
        if ($queryset->ordering && !isset($this->options['nosort'])) {
            $orders = array();
            foreach ($queryset->ordering as $sort) {
                $dir = 'ASC';
                if (substr($sort, 0, 1) == '-') {
                    $dir = 'DESC';
                    $sort = substr($sort, 1);
                }
                list($field) = $this->getField($sort, $model);
                $orders[] = $field.' '.$dir;
            }
            $sort = ' ORDER BY '.implode(', ', $orders);
        }

        // Include related tables
        $fields = array();
        $table = $model::$meta['table'];
        if ($queryset->related) {
            $fields = array($this->quote($table).'.*');
            foreach ($queryset->related as $rel) {
                // XXX: This is ugly
                list($t) = $this->getField($rel, $model,
                    array('table'=>true));
                $fields[] = $t.'.*';
            }
        // Support only retrieving a list of values rather than a model
        } elseif ($queryset->values) {
            foreach ($queryset->values as $v) {
                list($fields[]) = $this->getField($v, $model);
            }
        } else {
            $fields[] = $this->quote($table).'.*';
        }

        $joins = $this->getJoins();
        $sql = 'SELECT '.implode(', ', $fields).' FROM '
            .$this->quote($table).$joins.$where.$sort;
        if ($queryset->limit)
            $sql .= ' LIMIT '.$queryset->limit;
        if ($queryset->offset)
            $sql .= ' OFFSET '.$queryset->offset;

        return new MysqlExecutor($sql, $this->params);
    }

    function _compileUpdate($model) {
        $pk = $model::$meta['pk'];
        if (!is_array($pk)) $pk=array($pk);
        $filter = $fields = array();
        foreach ($model->dirty as $field=>$old) {
            if ($model->__new__ || !in_array($field, $pk)) {
                if (($val = $model->get($field)) instanceof SqlFunction)
                    $fields[] = $this->quote($field)
                        .' = '.$val->toSql();
                else
                    $fields[] = $this->quote($field)
                        .' = '.$this->input($val);
            }
        }
        return ' SET '.implode(', ', $fields);
    }

    function compileUpdate($model) {
        $sql = 'UPDATE '.static::$meta['table'];
        $sql .= $this->_compileUpdate($model);

        foreach ($pk as $p)
            $filter[] = $p.' = '.$this->input($model->get($p));
        $sql .= ' WHERE '.implode(' AND ', $filter);
        $sql .= ' LIMIT 1';

        return new MysqlExecutor($sql, $this->params);
    }

    function compileInsert($model) {
        $sql = 'INSERT INTO '.$model::$meta['table'];
        $sql .= $this->_compileUpdate($model);

        return new MysqlExecutor($sql, $this->params);

    }

    function compileDelete($model) {
        $table = $model::$meta['table'];
        $sql = 'DELETE FROM '.$this->quote($table);
        $filter = array();

        if (!$pk) $pk = $model::$meta['pk'];
        if (!is_array($pk)) $pk=array($pk);

        foreach ($pk as $p)
            $filter[] = $p.' = '.$this->input($this->get($p));
        $sql .= ' WHERE '.implode(' AND ', $filter).' LIMIT 1';

        return new MysqlExecutor($sql, $this->params);
    }

    // Returns meta data about the table used to build queries
    function inspectTable($table) {
    }
}

class MysqlExecutor {

    var $stmt;
    var $fields = array();

    var $sql;
    var $params;

    function __construct($sql, $params) {
        $this->sql = $sql;
        $this->params = $params;
    }

    function _prepare() {
        $this->execute();
        $this->_setup_output();
        $this->stmt->store_result();
    }

    function execute($engine) {
        if (!($this->stmt = $engine->prepare($this->sql)))
            throw new Exception('Unable to prepare query: '.db_error()
                .' '.$this->sql);
        if (count($this->params))
            $this->_bind($this->params);
        $this->stmt->execute();
    }


    function _bind($params) {
        if (count($params) != $this->stmt->param_count)
            throw new Exception('Parameter count does not match query');

        $types = '';
        $ps = array();
        foreach ($params as &$p) {
            if (is_int($p) || is_bool($p))
                $types .= 'i';
            elseif (is_float($p))
                $types .= 'd';
            elseif (is_string($p))
                $types .= 's';
            // TODO: Emit error if param is null
            $ps[] = &$p;
        }
        unset($p);
        array_unshift($ps, $types);
        call_user_func_array(array($this->stmt,'bind_param'), $ps);
    }

    function _setup_output() {
        $meta = $this->stmt->result_metadata();
        while ($f = $meta->fetch_field())
            $this->fields[] = $f;
    }

    // Iterator interface
    function rewind() {
        if (!isset($this->stmt))
            $this->_prepare();
        $this->stmt->data_seek(0);
    }

    function next() {
        $status = $this->stmt->fetch();
        if ($status === false)
            throw new Exception($this->stmt->error_list . db_error());
        elseif ($status === null) {
            $this->close();
            return false;
        }
        return true;
    }

    function getArray() {
        $output = array();
        $variables = array();

        if (!isset($this->stmt))
            $this->_prepare();

        foreach ($this->fields as $f)
            $variables[] = &$output[$f->name]; // pass by reference

        call_user_func_array(array($this->stmt, 'bind_result'), $variables);
        if (!$this->next())
            return false;
        return $output;
    }

    function getRow() {
        $output = array();
        $variables = array();

        if (!isset($this->stmt))
            $this->_prepare();

        foreach ($this->fields as $f)
            $variables[] = &$output[]; // pass by reference

        call_user_func_array(array($this->stmt, 'bind_result'), $variables);
        if (!$this->next())
            return false;
        return $output;
    }

    function getStruct() {
        $output = array();
        $variables = array();

        if (!isset($this->stmt))
            $this->_prepare();

        foreach ($this->fields as $f)
            $variables[] = &$output[$f->table][$f->name]; // pass by reference

        // TODO: Figure out what the table alias for the root model will be
        call_user_func_array(array($this->stmt, 'bind_result'), $variables);
        if (!$this->next())
            return false;
        return $output;
    }

    function close() {
        if (!$this->stmt)
            return;

        $this->stmt->close();
        $this->stmt = null;
    }

    function insert_id() {
        return $this->stmt->insert_id;
    }
    function affected_rows() {
        return $this->stmt->affected_rows;
    }

    function __toString() {
        return $this->sql;
    }
}
