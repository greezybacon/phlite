<?php

namespace Phlite\Db\Backends\MySQL;

use Phlite\Db\Compile\Statement;
use Phlite\Db\Compile\SqlCompiler;
use Phlite\Db\Manager;
use Phlite\Db\Model\ModelBase;
use Phlite\Db\Model\QuerySet;
use Phlite\Db\Util;

class Compiler extends SqlCompiler {

    protected $input_join_count = 0;
    protected $conn;

    static $operators = array(
        'exact' => '%1$s = %2$s',
        'contains' => array('self', '__contains'),
        'startswith' => array('self', '__startswith'),
        'endswith' => array('self', '__endswith'),
        'gt' => '%1$s > %2$s',
        'lt' => '%1$s < %2$s',
        'gte' => '%1$s >= %2$s',
        'lte' => '%1$s <= %2$s',
        'isnull' => array('self', '__isnull'),
        'like' => '%1$s LIKE %2$s',
        'hasbit' => '%1$s & %2$s != 0',
        'in' => array('self', '__in'),
    );

    // Thanks, http://stackoverflow.com/a/3683868
    function like_escape($what, $e='\\') {
        return str_replace(array($e, '%', '_'), array($e.$e, $e.'%', $e.'_'), $what);
    }
    
    function __contains($a, $b) {
        # {%a} like %{$b}%
        # Escape $b
        $b = $this->like_escape($b);
        return sprintf('%s LIKE %s', $a, $this->input("%$b%"));
    }
    function __startswith($a, $b) {
        $b = $this->like_escape($b);
        return sprintf('%s LIKE %s', $a, $this->input("%$b"));
    }
    function __endswith($a, $b) {
        $b = $this->like_escape($b);
        return sprintf('%s LIKE %s', $a, $this->input("$b%"));
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

    function __isnull($a, $b) {
        return $b
            ? sprintf('%s IS NULL', $a)
            : sprintf('%s IS NOT NULL', $a);
    }

    function compileJoin($tip, $model, $alias, $info, $extra=false) {
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
            // Support a constant constraint with
            // "'constant'" => "Model.field_name"
            if ($local[0] == "'") {
                $constraints[] = sprintf("%s.%s = %s",
                    $alias, $this->quote($right),
                    $this->input(trim($local, '\'"'), self::SLOT_JOINS)
                );
            }
            else {
                $constraints[] = sprintf("%s.%s = %s.%s",
                    $table, $this->quote($local), $alias,
                    $this->quote($right)
                );
            }
        }
        // Support extra join constraints
        if ($extra instanceof Util\Q) {
            $constraints[] = $this->compileQ($extra, $model, self::SLOT_JOINS);
        }
        return $join.$this->quote($rmodel::$meta['table'])
            .' '.$alias.' ON ('.implode(' AND ', $constraints).')';
    }
    
    function addParam($what, $slot=false) {
        switch ($slot) {
        case self::SLOT_JOINS:
            // This should be inserted before the WHERE inputs
            array_splice($this->params, $this->input_join_count++, 0,
                array($what));
            break;
        default:
            $this->params[] = $what;
        }
        return '?';
    }

    function quote($what) {
        return "`$what`";
    }
    
    function escape($what, $quote=true) {
        // Use a connection to do this
    }

    /**
     * getWhereClause
     *
     * This builds the WHERE ... part of a DML statement. This should be
     * called before ::getJoins(), because it may add joins into the
     * statement based on the relationships used in the where clause
     */
    protected function getWhereHavingClause($queryset) {
        $model = $queryset->model;
        $constraints = $this->compileConstraints($queryset->constraints, $model);
        $where = $having = array();
        foreach ($constraints as $C) {
            if ($C->type == CompiledExpression::TYPE_WHERE)
                $where[] = $C;
            else
                $having[] = $C;
        }
        if ($where)
            $where = ' WHERE '.implode(' AND ', $where);
        if ($having)
            $having = ' HAVING '.implode(' AND ', $having);
        return array($where ?: '', $having ?: '');
    }

    function compileCount(QuerySet $queryset) {
        $model = $queryset->model;
        $table = $model::$meta['table'];
        list($where, $having) = $this->getWhereHavingClause($queryset);
        $joins = $this->getJoins();
        $sql = 'SELECT COUNT(*) AS count FROM '.$this->quote($table).$joins.$where;
        $exec = Manager::getConnection($model)->getExecutor(
            new Statement($sql, $this->params));
        $row = $exec->getArray();
        return $row['count'];
    }

    function compileSelect(QuerySet $queryset) {
        $model = $queryset->model;
        
        // Use an alias for the root model table
        $table = $model::$meta['table'];
        $this->joins[''] = array('alias' => ($rootAlias = $this->nextAlias()));

        // Compile the WHERE clause
        $this->annotations = $queryset->annotations ?: array();
        list($where, $having) = $this->getWhereHavingClause($queryset);

        // Compile the ORDER BY clause
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
                // TODO: Throw exception if $field can be indentified as
                //       invalid
                $orders[] = $field.' '.$dir;
            }
            $sort = ' ORDER BY '.implode(', ', $orders);
        }

        // Compile the field listing
        $fields = array();
        $table = $this->quote($table).' '.$rootAlias;
        $group_by = $fieldMap = array();
        
        // Handle related tables
        if ($queryset->related) {
            $count = 0;
            $theseFields = array();
            $defer = $queryset->defer ?: array();
            // Add local fields first
            $model::_inspect();
            foreach ($model::$meta['fields'] as $f) {
                // Handle deferreds
                if (isset($defer[$f]))
                    continue;
                $fields[] = $rootAlias . '.' . $this->quote($f);
                $theseFields[] = $f;
            }
            $fieldMap[] = array($theseFields, $model);
            // Add the JOINs to this query
            foreach ($queryset->related as $sr) {
                // XXX: Sort related by the paths so that the shortest paths
                //      are resolved first when building out the models.
                $full_path = '';
                $parts = array();
                // Track each model traversal and fetch data for each of the
                // models in the path of the related table
                foreach (explode('__', $sr) as $field) {
                    $full_path .= $field;
                    $parts[] = $field;
                    $theseFields = array();
                    list($alias, $fmodel) = $this->getField($full_path, $model,
                        array('table'=>true, 'model'=>true));
                    $fmodel::_inspect();
                    foreach ($fmodel::$meta['fields'] as $f) {
                        // Handle deferreds
                        if (isset($defer[$sr . '__' . $f]))
                            continue;
                        $fields[] = $alias . '.' . $this->quote($f);
                        $theseFields[] = $f;
                    }
                    $fieldMap[] = array($theseFields, $fmodel, $parts);
                    $full_path .= '__';
                }
            }
        }
        // Support retrieving only a list of values rather than a model
        elseif ($queryset->values) {
            foreach ($queryset->values as $v) {
                list($f) = $this->getField($v, $model);
                if ($f instanceof Util\SqlFunction)
                    $fields[] = $f->toSql($this, $model);
                else
                    $fields[] = $f;
            }
        }
        // Simple selection from one table
        else {
            if ($queryset->defer) {
                $model::_inspect();
                foreach ($model::$meta['fields'] as $f) {
                    if (isset($queryset->defer[$f]))
                        continue;
                    $fields[] = $rootAlias .'.'. $this->quote($f);
                }
            }
            else {
                $fields[] = $rootAlias.'.*';
            }
        }
        // Add in annotations
        if ($queryset->annotations) {
            foreach ($queryset->annotations as $A) {
                $fields[] = $A->toSql($this, $model, true);
                // TODO: Add to last fieldset in fieldMap
            }
            foreach ($model::$meta['pk'] as $pk)
                $group_by[] = $rootAlias .'.'. $pk;
        }
        $joins = $this->getJoins();
        $group_by = ($group_by) ? ' GROUP BY '.implode(',', $group_by) : '';
        
        $sql = 'SELECT '.implode(', ', $fields).' FROM '
            .$table.$joins.$where.$group_by.$having.$sort;
        if ($queryset->limit)
            $sql .= ' LIMIT '.$queryset->limit;
        if ($queryset->offset)
            $sql .= ' OFFSET '.$queryset->offset;
        switch ($queryset->lock) {
        case QuerySet::LOCK_EXCLUSIVE:
            $sql .= ' FOR UPDATE';
            break;
        case QuerySet::LOCK_SHARED:
            $sql .= ' LOCK IN SHARE MODE';
            break;
        }

        return new Statement($sql, $this->params, $fieldMap);
    }

    function __compileUpdateSet($model, array $pk) {
        $fields = array();
        foreach ($model->dirty as $field=>$old) {
            if ($model->__new__ or !in_array($field, $pk)) {
                $fields[] = sprintf('%s = %s', $this->quote($field),
                    $this->input($model->get($field)));
            }
        }
        return ' SET '.implode(', ', $fields);
    }

    function compileUpdate(ModelBase $model) {
        $pk = $model::$meta['pk'];
        $sql = 'UPDATE '.$this->quote($model::$meta['table']);
        $sql .= $this->__compileUpdateSet($model, $pk);
        // Support PK updates
        $criteria = array();
        foreach ($pk as $f) {
            $criteria[$f] = @$model->dirty[$f] ?: $model->get($f);
        }
        $sql .= ' WHERE '.$this->compileQ(new Q($criteria), $model);
        $sql .= ' LIMIT 1';

        return new Statement($sql, $this->params);
    }

    function compileInsert(ModelBase $model) {
        $pk = $model::$meta['pk'];
        $sql = 'INSERT INTO '.$this->quote($model::$meta['table']);
        $sql .= $this->__compileUpdateSet($model, $pk);

        return new Statement($sql, $this->params);
    }

    function compileDelete(ModelBase $model) {
        $table = $model::$meta['table'];

        $where = ' WHERE '.implode(' AND ', $this->compileConstraints($model->pk));
        $sql = 'DELETE FROM '.$this->quote($table).$where.' LIMIT 1';
        return new Statement($sql, $this->params);
    }

    function compileBulkDelete(QuerySet $queryset) {
        $model = $queryset->model;
        $table = $model::$meta['table'];
        list($where, $having) = $this->getWhereHavingClause($queryset);
        $joins = $this->getJoins();
        $sql = 'DELETE '.$this->quote($table).'.* FROM '
            .$this->quote($table).$joins.$where;
        return new Statement($sql, $this->params);
    }

    function compileBulkUpdate(QuerySet $queryset, array $what) {
        $model = $queryset->model;
        $table = $model::$meta['table'];
        $set = array();
        foreach ($what as $field=>$value)
            $set[] = sprintf('%s = %s', $this->quote($field), $this->input($value));
        $set = implode(', ', $set);
        list($where, $having) = $this->getWhereHavingClause($queryset);
        $joins = $this->getJoins();
        $sql = 'UPDATE '.$this->quote($table).' SET '.$set.$joins.$where;
        return new Statement($sql, $this->params);
    }

    // Returns meta data about the table used to build queries
    function inspectTable($table) {
        static $cache = array();

        // XXX: Assuming schema is not changing — add support to track
        //      current schema
        if (isset($cache[$table]))
            return $cache[$table];

        $sql = 'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS '
            .'WHERE TABLE_NAME = '.db_input($table).' AND TABLE_SCHEMA = DATABASE() '
            .'ORDER BY ORDINAL_POSITION';
        $ex = new MysqlExecutor(new Statement($sql, array()));
        $columns = array();
        while (list($column) = $ex->getRow()) {
            $columns[] = $column;
        }
        return $cache[$table] = $columns;
    }
    
    function compileCreate($modelClass) {
        // TODO:
    }
}