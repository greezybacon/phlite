<?php

namespace Phlite\Request;

class Session implements ArrayAccess {

    var $root;
    var $session;

    function __construct($root=':s') {
        $this->root = $root;
        $this->session = &$_SESSION[$root];
    }

    function offsetGet($key) {
        return $this->session[$key];
    }

    function offsetSet($key, &$value) {
        $this->session[$key] = &$value;
    }

    function offsetExists($key) {
        return isset($this->session[$key]);
    }

    function offsetUnset($key) {
        unset($this->session[$key]);
    }
}

class SessionMiddleware extends Middleware {
    
}
