<?php

namespace Phlite\Util;

/**
 * Light re-implementation of the Python dict, which is primarily focused 
 * on key=>value mapping, objects are automatically hashed using
 * spl_object_hash. Other things raise KeyError when added to the dict.
 *
 * Sorting functions have been dropped, but could be borrowed from the
 * ArrayObject itself:
 *
 * >>> $a = new Dict(['z', 'y', 'x']);
 * >>> $b = new ArrayObject($a->values());
 * >>> $b->asort();
 *
 * This class supports objects used as keys as well.
 *
 * TODO: Fix serialization with objects (rekey on wakeup)
 */
class Dict 
implements \Iterator, \ArrayAccess, \Serializable, \Countable {
    
    protected $storage = array();
    
    function __construct(array $array=array()) {
        $this->update($array);
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
            $rv = $this->storage[$key][1];
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
        if (is_object($key))
            $key = spl_object_hash($key);
        if (isset($this->storage[$key]))
            return $this->storage[$key][1];
        else
            return $default;
    }
    
    function update(/* Iterable */ $other) {
        foreach ($other as $k=>$v)
            $this[$k] = $v;
    }
    
    function values() {
        $values = array();
        foreach ($this as $k=>$v)
            $values[] = $v[1];
        return $values;
    }
    
    function items() {
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
    
    // Iterator
    function current() { return current($this->storage)[1]; }
    function key() { return current($this->storage)[0]; }
    function next() { return next($this->storage); }
    function rewind() { return reset($this->storage); }
    function valid() { return null != key($this->storage); }
    
    // ArrayAccess
    function offsetExists($offset) { 
        if (is_object($offset))
            $offset = spl_object_hash($offset);
        return isset($this->storage[$offset]); 
    }
    function offsetGet($offset) {
        if (is_object($offset))
            $offset = spl_object_hash($offset);
        if (!isset($this->storage[$offset]))
            throw new \OutOfBoundsException();
        return $this->storage[$offset][1];
    }
    function offsetSet($offset, $value) { 
        $key = is_object($offset) ? spl_object_hash($offset) : $offset;
        $this->storage[$key] = array($offset, $value);
    }
    function offsetUnset($offset) {
        if (is_object($offset))
            $offset = spl_object_hash($offset);
         unset($this->storage[$offset]);
    }
    
    // Serializable
    function serialize() { 
        return serialize($this->items()); 
    }
    function unserialize($what) { 
        $this->clear();
        foreach (unserialize($what) as $i) {
            list($k, $v) = $i;
            $this[$k] = $v;
        }
        $this->__wakeup();
    }
    
    function __toString() {
        foreach ($this->storage as $v) {
            list($key, $value) = $v;
            $items[] = (string) $key . '=> ' . (string) $value;
        }
        return '{'.implode(', ', $items).'}';
    }
    
    function asArray() {
        $array = array();
        foreach ($this->storage as $v) {
            $array[$v[0]] = $v[1];
        }
        return $array;
    }
}