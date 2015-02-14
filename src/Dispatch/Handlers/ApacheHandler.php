<?php

namespace Phlite\Dispatch\Handlers;

use Phlite\Dispatch\BaseHandler;

class ApacheHandler extends BaseHandler {
    function getPathInfo() {
        return $_SERVER['PATH_INFO'];
    }
}
