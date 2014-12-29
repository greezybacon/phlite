<?php

namespace Phlite\Text;

class BaseString implements \ArrayAccess {
    protected $string;
    protected $length;

    function __construct($string) {
        $this->string = $string;
        $this->length = strlen($string);
    }
    
    function offsetGet($offset) {
        return $this->string[$offset];
    }
    function offsetExists($offset) {
        return $offset < $this->length;
    }
    function offsetSet($offset, $value) {}
    function offsetUnset($offset) {}
}
