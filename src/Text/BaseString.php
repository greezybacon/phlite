<?php

namespace Phlite\Text;

class BaseString implements \ArrayAccess, \Countable {
    protected $string;

    function __construct($string='') {
        $this->string = (string) $string;
    }

    function append($what) {
        $this->string .= $what;
    }

    function length() {
        return strlen($this->string);
    }
    
    // ---- Countable interface -------------------------------
    function count() {
        return $this->length();
    }
    
    // ---- ArrayAccess interface -----------------------------
    function offsetGet($offset) {
        if ($offset < 0)
            return substr($this->string, $offset, 1);
        return $this->string[$offset];
    }
    function offsetExists($offset) {
        return abs($offset) < $this->length();
    }
    function offsetSet($offset, $value) {}
    function offsetUnset($offset) {}
    
    function set($what) {
        $this->string = $what;
    }
    function get() {
        return $this->string;
    }
    
    // ---- (string) coersion ---------------------------------
    function __toString() { 
        return $this->string;
    }
}
