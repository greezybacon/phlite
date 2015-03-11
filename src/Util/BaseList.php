<?php

namespace Phlite\Util;

abstract class BaseList
implements \Countable, \IteratorAggregate, \Serializable, \JsonSerializable {
    
    protected $storage = array();
    
    /**
     * Sort the list in place.
     * 
     * Parameters:
     * $key - (callable|int) A callable function to produce the sort keys
     *      or one of the SORT_ constants used by the array_multisort
     *      function
     * $reverse - (bool) true if the list should be sorted descending
     */
    function sort($key=false, $reverse=false) {
        if (is_callable($key)) {
            $keys = array_map($key, $this->storage);
            array_multisort($keys, $this->storage, 
                $reverse ? SORT_DESC : SORT_ASC);
        }
        elseif ($key) {
            array_multisort($this->storage,
                $reverse ? SORT_DESC : SORT_ASC, $key);
        }
        elseif ($reverse) {
            rsort($this->storage);
        }
        else
            sort($this->storage);
    }
    
    function reverse() {
        return array_reverse($this->storage);
    }
    
    function filter($callable) {
        $new = new static();
        foreach ($this->storage as $i=>$v)
            if ($callable($v, $i))
                $new[] = $v;
        return $new;
    }
    
    // IteratorAggregate
    function getIterator() {
        return new \ArrayIterator($this->storage);
    }
    
    // Countable
    function count($mode=COUNT_NORMAL) {
        return count($this->storage, $mode);
    }
    
    // Serializable
    function serialize() {
        return serialize($this->storage);
    }
    function unserialize($what) {
        $this->storage = unserialize($what);
    }
    
    // JsonSerializable
    function jsonSerialize() {
        return $this->storage;
    }
}