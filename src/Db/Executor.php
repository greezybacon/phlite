<?php

namespace Phlite\Db;

abstract class Executor implements Iterator {

    var $stmt;
    var $sql;
    var $params;

    function __construct($sql, $params) {
        $this->sql = $sql;
        $this->params = $params;
    }

    function execute($engine) {
        if (!($this->stmt = $engine->prepare($this->sql)))
            throw new Exception('Unable to prepare query: '.db_error()
                .' '.$this->sql);
        if (count($this->params))
            $this->bind($this->params);
        $this->stmt->execute();
    }

    // Iterator interface
    abstract function rewind();

    abstract function next();

    abstract function getArray();

    abstract function getRow();

    abstract function getStruct();

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
