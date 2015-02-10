<?php

namespace Phlite\Text;

class BaseString implements \ArrayAccess {
    protected $string;
    protected $length;

    function __construct($string='') {
        $this->string = $string;
        $this->length = strlen($string);
    }

    function append($what, $length=false) {
        $this->string .= $what;
        $this->length += strlen($what);
    }
    
    function offsetGet($offset) {
        return $this->string[$offset];
    }
    function offsetExists($offset) {
        return $offset < $this->length;
    }
    function offsetSet($offset, $value) {}
    function offsetUnset($offset) {}
        
    function __toString() { 
        return $this->string;
    }
    function get() {
        return $this->string;
    }
    function length() {
        return $this->length;
    }
    function set($what) {
        $this->string = $what;
        $this->length = strlen($what);
    }
}
