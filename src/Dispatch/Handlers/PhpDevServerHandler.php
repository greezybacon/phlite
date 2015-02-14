<?php

namespace Phlite\Dispatch\Handlers;

use Phlite\Dispatch\BaseHandler;

class PhpDevServerHandler extends BaseHandler {
    function getPathInfo() {
        if (isset($_SERVER['PATH_INFO']))
            return $_SERVER['PATH_INFO'];
        
        return parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    }
}