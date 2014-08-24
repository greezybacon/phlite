<?php

namespace Phlite\View;

use Phlite\Http\Exception\MethodNotAllowed;

abstract class BaseView {
    function __invoke() {
        $method = strtolower($_SERVER['REQUEST_METHOD']);

        switch ($method) {
        case 'get':
        case 'post':
        case 'delete':
        case 'put':
        case 'head':
            return call_user_func_array(array($this, $method),
                func_get_args());
        default:
            $this->_sendAllowedMethods();
        }
    }

    private function _sendAllowedMethods($request) {
        $methods = array();
        $class = new \ReflectionClass($this);
        foreach (array('get', 'post', 'delete', 'put', 'head') as $m) {
            if (($R = $class->getMethod($m))
                && ($R->getDeclaringClass()->getName() != __CLASS__)
            ) {
                $methods[] = $m;
            }
        }
        // Send HTTP/405 — Method Not Allowed, which requires a list of
        // allowed methods. Assume all overridden view methods are allowed.
        throw new MethodNotAllowed($methods, 'Method not implemented');
    }

    function get($request) {
        $this->_sendAllowedMethods($request);
    }

    function post($request) {
        $this->_sendAllowedMethods($request);
    }

    function put() {
        $this->_sendAllowedMethods($request);
    }

    function delete() {
        $this->_sendAllowedMethods($request);
    }

    function head() {
        $this->_sendAllowedMethods($request);
    }

	function templateResponse($template, $context=array()) {
		$response = new TemplateResponse($template);
	}
}
