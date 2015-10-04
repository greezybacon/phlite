<?php

namespace Phlite\Db\Model;

use Phlite\Db\Manager;

abstract class ResultSet
implements \Iterator, \ArrayAccess, \Countable {
    var $resource;
    var $stmt;
    var $position = 0;
    var $queryset;
    var $cache = array();

    function __construct($queryset=false) {
        $this->queryset = $queryset;
        if ($queryset) {
            $this->stmt = $queryset->getQuery();
            $connection = Manager::getConnection($this->model);
        }
    }

    function prime() {
        if (!isset($this->resource) && $this->queryset)
            $this->resource = $this->queryset->getQuery();
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

    // Countable interface
    function count() {
        return count($this->asArray());
    }

    /**
     * Sort the resultset list in place. This would be useful to change the
     * sorting order of the items in the list without fetching the list from
     * the database again.
     *
     * Parameters:
     * $key - (callable|int) A callable function to produce the sort keys
     *      or one of the SORT_ constants used by the array_multisort
     *      function
     * $reverse - (bool) true if the list should be sorted descending
     *
     * Returns:
     * This resultset list for chaining and inlining.
     */
    function sort($key=false, $reverse=false) {
        // Fetch all records into the cache
        $this->asArray();
        if (is_callable($key)) {
            array_multisort(
                array_map($key, $this->cache),
                $reverse ? SORT_DESC : SORT_ASC,
                $this->cache);
        }
        elseif ($key) {
            array_multisort($this->cache,
                $reverse ? SORT_DESC : SORT_ASC, $key);
        }
        elseif ($reverse) {
            rsort($this->cache);
        }
        else
            sort($this->cache);
        return $this;
    }

    /**
     * Reverse the list item in place. Returns this object for chaining
     */
    function reverse() {
        $this->asArray();
        array_reverse($this->cache);
        return $this;
    }
}
