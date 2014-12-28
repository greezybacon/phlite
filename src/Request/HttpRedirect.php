<?php

namespace Phlite\Request;

class HttpRedirect extends HttpResponse {
    
    var $delay;
    
    function __construct($stream, $status=302, $delay=0) {
        parent::__construct($stream, $status);
        $this->delay = $delay;
    }
    
    function output() {
        $iis = strpos($_SERVER['SERVER_SOFTWARE'], 'IIS') !== false;
        @list($name, $version) = explode('/', $_SERVER['SERVER_SOFTWARE']);
        // Legacy code for older versions of IIS that would not emit the
        // correct HTTP status and headers when using the `Location`
        // header alone
        if ($iis && version_compare($version, '7.0', '<')) {
            header("Refresh: $this->delay; URL=$this->body");
        }else{
            header("Location: $this->body");
        }   
        print('<html></html>');
    }   
}