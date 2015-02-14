<?php

namespace Phlite\Util;

/**
 * Light re-implementation of the Python dict, which is primarily focused 
 * on key=>value mapping. This is based on the PHP ArrayObject, but is
 * actually extendable and allows access to the protected $storage array.
 *
 */
class ArrayObject 
implements \IteratorAggregate, \ArrayAccess, \Serializable, \Countable {
    
    protected $storage = array();
    
    function __construct(array $array=array()) {
        $this->storage = $array;
    }
    
    function clear() {
        $this->storage = array();
    }
    
    function copy() {
        return clone $this;
    }
    
    function keys() {
        return array_keys($this->storage);
    }
    
    function pop($key, $default=null) {
        if (isset($this->storage[$key])) {
            $rv = $this->storage[$key];
            unset($this->storage[$key]);
            return $rv;
        }
        return $defaut;
    }
    
    function setDefault($key, $default=false) {
        if (!isset($this[$key]))
            $this[$key] = $default;
        return $this[$key];
    }
    
    function get($key, $default=null) {
        if (isset($this->storage[$key]))
            return $this->storage[$key];
        else
            return $default;
    }
    
    function update(/* Iterable */ $other) {
        foreach ($other as $k=>$v)
            $this[$k] = $v;
    }
    
    function values() {
        return array_values($this->storage);
    }
    
    static function fromKeys(Traversable $keys, $value=false) {
        $o = new static();
        foreach ($keys as $k)
            $o[$k] = $value;
        return $o;
    }
    
    // Countable
    function count() { return count($this->storage); }
    
    // IteratorAggregate
    function getIterator() { return new \ArrayIterator($this->storage); }
    
    // ArrayAccess
    function offsetExists($offset) { 
        return isset($this->storage[$offset]); 
    }
    function offsetGet($offset) {
        if (!isset($this->storage[$offset]))
            throw new \OutOfBoundsException();
        return $this->storage[$offset];
    }
    function offsetSet($key, $value) { 
        $this->storage[$key] = $value;
    }
    function offsetUnset($offset) {
         unset($this->storage[$offset]);
    }
    
    // Serializable
    function serialize() { 
        return serialize($this->storage); 
    }
    function unserialize($what) { 
        $this->storage = unserialize($what);
    }
    
    function __toString() {
        foreach ($this->storage as $key=>$v) {
            $items[] = (string) $key . '=> ' . (string) $value;
        }
        return '{'.implode(', ', $items).'}';
    }
    
    function asArray() {
        return $this->storage;
    }
}