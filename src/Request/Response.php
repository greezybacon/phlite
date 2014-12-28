<?php

namespace Phlite\Request;

interface Response {
    function setHandler($handler);
    function output();
    
    function getStatusCode();
    function addHeader($header, $value);
}
