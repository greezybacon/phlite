<?php

namespace Phlite\Dispatch\Handlers;

use Phlite\Dispatch\BaseHandler;

class ApacheHandler extends BaseHandler {
    function getPathInfo() {
        return @$_SERVER['PATH_INFO']
            ?: @$_SERVER['ORIG_PATH_INFO']
            ?: @$_SERVER['REDIRECT_PATH_INFO']
            // Attempt to discover path_info
            ?: substr($_SERVER['PHP_SELF'],
                strpos($_SERVER['PHP_SELF'], $_SERVER['SCRIPT_NAME'])
                    + strlen($_SERVER['SCRIPT_NAME']));
    }
}
