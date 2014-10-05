<?php

namespace Phlite\Db\Model;

use Phlite\Db\Manager;

abstract class ResultSet implements \Iterator, \ArrayAccess {
    var $resource;
    var $position = 0;
    var $queryset;
    var $cache = array();

    function __construct($queryset=false) {
        $this->queryset = $queryset;
        if ($queryset) {
            $this->model = $queryset->model;
            $stmt = $queryset->getQuery();
            $connection = Manager::getConnection($this->model);
            $this->resource = $connection->getExecutor($stmt);
        }
    }

    abstract function fillTo($index);

    function asArray() {
        $this->fillTo(PHP_INT_MAX);
        return $this->cache;
    }

    // Iterator interface
    function rewind() {
        $this->position = 0;
    }
    function current() {
        $this->fillTo($this->position);
        return $this->cache[$this->position];
    }
    function key() {
        return $this->position;
    }
    function next() {
        $this->position++;
    }
    function valid() {
        $this->fillTo($this->position);
        return count($this->cache) > $this->position;
    }

    // ArrayAccess interface
    function offsetExists($offset) {
        $this->fillTo($offset);
        return $this->position >= $offset;
    }
    function offsetGet($offset) {
        $this->fillTo($offset);
        return $this->cache[$offset];
    }
    function offsetUnset($a) {
        throw new \Exception(sprintf(__('%s is read-only'), get_class($this)));
    }
    function offsetSet($a, $b) {
        throw new \Exception(sprintf(__('%s is read-only'), get_class($this)));
    }
}