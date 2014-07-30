<?php

namespace Phlite\Util;

class ErrorSignaler {

    static function install() {
        $inst = new static();
        register_shutdown_function(array($inst, '__onShutdown'));
        set_exception_handler(array($inst, '__onUnhandledException'));
    }
    
    function onShutdown() {
        $error = error_get_last();
        if ($error !== null) {
            $info = array('error' => $error);
            Signal::send('php.fatal', $this, $info);
        }
    }

    function onUnhandledException($ex) {
        $info = array('exception' => $ex);
        Signal::send('php.exception', $this, $info);
    }
}
