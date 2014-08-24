<?php

namespace Phlite\View;

class BoundView {
    
    var $view;
    var $args;
    
    function __construct($view, $args=array()) {
        $this->view = $view;
        $this->args = $args;
    }
    
    function __invoke($request) {
        $args = array_merge(array($request), $this->args);
        return call_user_func_array($this->view, $args);
    }
}