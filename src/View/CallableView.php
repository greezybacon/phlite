<?php

namespace Phlite\Dispatcher;

use Phlite\Dispatcher\Viewable;

class CallableView 
    implements Viewable {

    var $callable;
    
    function __construct($callable) {
        if (!is_callable($callable))
            throw new Exception("View function must be callable");
        $this->callable = $callable;
    }

    function as_view($args) {
        return call_user_func_array($this->callable, (array) $args);
    }
}
