<?php

namespace Phlite\Db\Backends\MySQL;

use Phlite\Db;
use Phlite\Db\Exception;
use Phlite\Db\Compile\SqlExecutor;
use Phlite\Db\Compile\Statement;

class MySQLiQuery
implements SqlExecutor {

    var $stmt;

    var $backend;
    var $conn;
    var $res;   // Server resource / cursor

    // Timing
    var $time_start;
    var $time_prepare;
    var $time_fetch;

    function __construct(Statement $stmt, Db\Backend $bk) {
        $this->stmt = $stmt;
        $this->backend = $bk;
    }
    function __destruct() {
        $this->close();
    }

    // Array of [count, model] values representing which fields in the
    // result set go with witch model.  Useful for handling select_related
    // queries
    function getMap() {
        return $this->stmt->map;
    }

    function execute() {
        // Lazy connect to the database
        if (!isset($this->conn))
            $this->conn = $this->backend->getConnection();

        // TODO: Detect server/client abort, pause and attempt reconnection

        $start = $this->time_start = microtime(true);

        // Drop the parameters from the query
        $sql = $this->_unparameterize();
        if (!($this->res = $this->conn->query($sql, MYSQLI_STORE_RESULT)))
            throw new Exception\InconsistentModel(
                'Unable to execute query: '.$this->conn->error.': '.$sql);

        $this->time_prepare = microtime(true) - $start;
        return true;
    }

    /**
     * Remove the parameters from the string and replace them with escaped
     * values. This is reportedly faster for MySQL and equally as safe.
     */
    function _unparameterize() {
        if (!isset($this->conn))
            $this->conn = $this->backend->getConnection();
        $conn = $this->conn;
        return $this->stmt->toString(function($i) use ($conn) {
            if ($i instanceof \DateTime) {
                // TODO: Detect database timezone and convert accordingly
                // for a non-naive DateTime instance
                $i = $i->format('Y-m-d H:i:s');
            }
            $i = $conn->real_escape_string($i);
            if (is_string($i))
                return "'$i'";
            return $i;
        });
    }

    // Iterator interface
    function rewind() {
        if (!isset($this->res))
            $this->execute();
        $this->res->data_seek(0);
    }

    function fetchArray() {
        if (!isset($this->res))
            $this->execute();

        if (!($row = $this->res->fetch_assoc()))
            return $this->close();

        return $row;
    }

    function fetchRow() {
        if (!isset($this->res))
            $this->execute();

        if (!($row = $this->res->fetch_row()))
            return $this->close();

        return $row;
    }

    function close() {
        if (!$this->res)
            return;

        $total = microtime(true) - $this->time_start;
        $this->time_fetch = $total - $this->time_prepare;
        $this->stmt->log(['time'=>$total, 'fetch'=>$this->time_fetch, 'prepare'=>$this->time_prepare]);

        $this->res->close();
        $this->res = null;
    }

    function affected_rows() {
        return $this->conn->affected_rows;
    }

    function insert_id() {
        return $this->conn->insert_id;
    }
}
