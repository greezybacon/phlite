<?php

namespace Phlite\View;

/**
 * BoundView
 *
 * The bound view class takes a callable object, like a function or array,
 * and an array of arguments, and wraps them in a typed callable object. When
 * the object is called, the original object is invoked with the original args
 */
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