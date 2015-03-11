<?php

namespace Phlite\Util;

/**
 * Light re-implementation of the Python dict, which is primarily focused 
 * on key=>value mapping. This is based on the PHP ArrayObject, but is
 * actually extendable and allows access to the protected $storage array.
 *
 */
class ArrayObject 
extends BaseList
implements \ArrayAccess {
        
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
    
    /** 
     * Implode an array with the key and value pair giving a glue, a 
     * separator between pairs and the array to implode.
     *
     * @param string $glue The glue between key and value
     * @param string $separator Separator between pairs
     * @param array $array The array to implode
     * @return string The imploded array
     *
     * References:
     * http://us2.php.net/manual/en/function.implode.php
     */    
    function join($glue, $separator) {
        $string = array();
        foreach ( $this->storage as $key => $val ) { 
            $string[] = "{$key}{$glue}{$val}";
        }
        return implode( $separator, $string );
    }
    
    static function fromKeys(Traversable $keys, $value=false) {
        return new static(array_fill($keys, $value));
    }
    
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