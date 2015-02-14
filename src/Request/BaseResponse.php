<?php

namespace Phlite\Request;

abstract class BaseResponse
implements Response {

    var $handler;
    var $headers = array();

    function setHandler($handler) {
        $this->handler = $handler;
    }
    
    function addHeader($name, $value) {
        $this->headers[$name] = $value;
    }

    function sendHeaders() {
        foreach ($this->headers as $h=>$v)
            header("$h: $v");
    }
    
    abstract function output($request);
    
    function deleteCookie($name) {
        // Thanks, http://stackoverflow.com/a/5285982
        setcookie($name, 'deleted', 0, ROOT_PATH);
    }
    function setCookie($name, $value, $expires=null, $domain=null,
        $secure=false, $httponly=false
    ) {
        setcookie($name, $value);
    }
}
