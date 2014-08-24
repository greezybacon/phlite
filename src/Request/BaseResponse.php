<?php

namespace Phlite\Request;

abstract class BaseResponse {

    var $handler;
    var $headers = array();

    function setHandler($handler) {
        $this->handler = $handler;
    }
    
    function addHeader($name, $value) {
        $this->headers[$name] = $value;
    }

    function outputHeaders() {
        foreach ($this->headers as $h=>$v)
            header("$h: $v");
    }
    
    abstract function output();
}
