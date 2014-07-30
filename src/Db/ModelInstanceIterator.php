<?php

namespace Phlite\Db;

class ModelInstanceIterator implements Iterator, ArrayAccess {
    var $model;
    var $resource;
    var $cache = array();
    var $position = 0;
    var $queryset;

    function __construct($queryset=false) {
        if ($queryset) {
            $this->model = $queryset->model;
            $this->resource = $queryset->getQuery();
        }
    }

    function buildModel($row) {
        // TODO: Traverse to foreign keys
        return new $this->model($row); # nolint
    }

    function fillTo($index) {
        while ($this->resource && $index >= count($this->cache)) {
            if ($row = $this->resource->getArray()) {
                $this->cache[] = $this->buildModel($row);
            } else {
                $this->resource->close();
                $this->resource = null;
                break;
            }
        }
    }

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
        throw new Exception(sprintf('%s is read-only', get_class($this)));
    }
    function offsetSet($a, $b) {
        throw new Exception(sprintf('%s is read-only', get_class($this)));
    }
}
