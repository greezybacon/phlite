<?php

namespace Phlite\Request;

interface Response {
    function setHandler($handler);
    function output($request);
    
    function getStatusCode();
    function addHeader($header, $value);
}
