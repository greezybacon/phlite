<?php

namespace Phlite\Db\Backends\MySQL;

use Phlite\Db\Exception;
use Phlite\Db\Compile\SqlExecutor;

class MysqlExecutor implements SqlExecutor {

    var $stmt;
    var $fields = array();
    var $sql;
    var $params;
    // Array of [count, model] values representing which fields in the
    // result set go with witch model.  Useful for handling select_related
    // queries
    var $map;
    
    var $backend;

    function __construct(Statement $stmt, Backend $conn) {
        $this->sql = $stmt->sql;
        $this->params = $stmt->params;
        $this->map = $stmt->map;
        $this->backend = $conn;
    }
    
    function connect() {
        if ($this->conn)
            // No auto reconnect, use ::disconnect() first
            return;
        
        $user = $this->conn_info['USER'];
        $passwd = $this->conn_info['PASSWORD'];
        $host = $this->conn_info['HOST'];
        $options = $this->conn_info['OPTIONS'];
        
        //Assert
        if(!strlen($user) || !strlen($host))
            return NULL;

        if (!($this->conn = mysqli_init()))
            return NULL;

        // Setup SSL if enabled
        if (isset($options['ssl']))
            $this->conn->ssl_set(
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
        if (!@$this->conn->real_connect($host, $user, $passwd, null, $port, $socket))
            return NULL;

        //Select the database, if any.
        if(isset($options['DATABASE'])) $this->conn->select_db($options['DATABASE']);

        //set desired encoding just in case mysql charset is not UTF-8 - Thanks to FreshMedia
        @$this->conn->query('SET NAMES "utf8"');
        @$this->conn->query('SET CHARACTER SET "utf8"');
        @$this->conn->query('SET COLLATION_CONNECTION=utf8_general_ci');
        $this->conn->set_charset('utf8');

        @db_set_variable('sql_mode', '');

        // Start a new transaction -- disable autocommit
        $this->conn->autocommit(false);
    }

    function getMap() {
        return $this->map;
    }

    function _prepare() {
        $this->execute();
        $this->_setup_output();
    }

    function execute() {
        if (!($this->stmt = $this->conn->prepare($this->sql)))
            throw new Exception\DbError('Unable to prepare query: '.db_error()
                .' '.$this->sql);
        if (count($this->params))
            $this->_bind($this->params);
        if (!$this->stmt->execute() || !$this->stmt->store_result()) {
            throw new Exception\DbError('Unable to execute query: ' . $this->stmt->error);
        }
        return true;
    }

    function _bind($params) {
        if (count($params) != $this->stmt->param_count)
            throw new Exception\DbError(__('Parameter count does not match query'));

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
        call_user_func_array(array($this->stmt, 'bind_param'), $ps);
    }

    function _setup_output() {
        if (!($meta = $this->stmt->result_metadata()))
            throw new Exception\DbError(
                'Unable to fetch statment metadata: ', $this->stmt->error);
        $this->fields = $meta->fetch_fields();
        $meta->free_result();
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
            throw new Exception\DbError($this->stmt->error);
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

        if (!call_user_func_array(array($this->stmt, 'bind_result'), $variables))
            throw new Exception\OrmError('Unable to bind result: ' . $this->stmt->error);

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

        if (!call_user_func_array(array($this->stmt, 'bind_result'), $variables))
            throw new Exception\OrmError('Unable to bind result: ' . $this->stmt->error);

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

    function affected_rows() {
        return $this->stmt->affected_rows;
    }

    function insert_id() {
        return $this->stmt->insert_id;
    }
}