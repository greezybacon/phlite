<?php

namespace Phlite\Db\Backends\MySQL;

use Phlite\Db\Backend;
use Phlite\Db\Exception;
use Phlite\Db\Compile\SqlExecutor;
use Phlite\Db\Compile\Statement;

class MySQLiExecutor
implements SqlExecutor {

    var $stmt;
    var $fields = array();
    var $sql;
    var $params;
    // Array of [count, model] values representing which fields in the
    // result set go with witch model.  Useful for handling select_related
    // queries
    var $map;
    
    var $backend;
    var $conn;

    function __construct(Statement $stmt, Backend $bk) {
        $this->sql = $stmt->sql;
        $this->params = $stmt->params;
        $this->map = $stmt->map;
        $this->backend = $bk;
    }
    
    function getMap() {
        return $this->map;
    }

    function _prepare() {
        $this->execute();
        $this->_setup_output();
    }

    function execute() {
        if (!$this->conn)
            $this->conn = $this->backend->getConnection();
        
        // TODO: Detect server/client abort, pause and attempt reconnection
        
        if (!($this->stmt = $this->conn->prepare($this->sql)))
            throw new Exception\InconsistentModel('Unable to prepare query: '.$this->conn->error
                .': '.$this->sql);
        if (count($this->params))
            $this->_bind($this->params);
        if (!$this->stmt->execute() || !$this->stmt->store_result()) {
            throw new Exception\DbError('Unable to execute query: ' . $this->stmt->error);
        }
        return true;
    }

    function _bind($params) {
        if (count($params) != $this->stmt->param_count)
            throw new Exception\OrmException(__('Parameter count does not match query'));

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

    function fetchArray() {
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

    function fetchRow() {
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