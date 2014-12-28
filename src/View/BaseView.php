<?php

namespace Phlite\View;

use Phlite\Exception\ProgrammingError;
use Phlite\Http\Exception\MethodNotAllowed;
use Phlite\Security\Policy;

abstract class BaseView {
    
    /**
     * $policy
     *
     * Governing policy of this view. In order for the view to
     * be accessible, this Policy will be consulted via the ::checkUserAccess
     * method. This operation will be passed as the second argument. If
     * varying policies are required for different methods of the same view,
     * the view should be split so that each view and set of methods has
     * only one governing policy.
     */
    static $policy = false;
    
    function __invoke($request) {
        
        // Check associated policy, if any
        if (static::$policy) {
            $P = new static::$policy();
            if (!$P instanceof Policy)
                throw new ProgrammingError("Operation policies must implement Policy");
            $P->checkUserAccess($request, $this);
        }
        
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
        // Send HTTP/405 — Method Not Allowed, which requires a list of
        // allowed methods. Assume all overridden view methods are allowed.
        throw new MethodNotAllowed(static::getAllowedMethods());
    }
    
    static function getAllowedMethods() {
        $methods = array();
        $class = new \ReflectionClass($this);
        foreach (array('get', 'post', 'delete', 'put', 'head') as $m) {
            if (($R = $class->getMethod($m))
                && ($R->getDeclaringClass()->getName() != __CLASS__)
            ) {
                $methods[] = $m;
            }
        }
        return $methods;
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
