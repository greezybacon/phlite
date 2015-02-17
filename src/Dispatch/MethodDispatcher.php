<?php

namespace Phlite\Dispatch;

use Phlite\View;

class MethodDispatcher
implements Dispatcher {
    
    var $root;
    
    function __construct($root=false) {
        $this->root = $root;
    }    
    
    function resolve($url, $root=false) {
        // Drop leading slashes
        $url = ltrim($url, '/');
                
        // Split the URL by slashes
        $path = explode('/', $url);
        
        // Find the best next level to handle the path
        return $this->resolve2($root ?: $this->root ?: $this, $path);
    }
    
    protected function translate($what) {
        return preg_replace('`[^\pL\pN]`u', '_', $what);
    }
    
    function resolve2($context, array $path) {
        $original_path = $path;
        $head = array_shift($path);
        $args = $path;
        $head = $this->translate($head);
        
        // If the head is empty, then the URL ended in a slash (/), assume 
        // the index is implied
        if (!$head)
            $head = 'index';
        
        try {
            $method = new \ReflectionMethod($context, $head);
            
            // Ensure that the method is visible
            if (!$method->isPublic()) {
                return false;
            }
                        
            // Check if argument count matches the rest of the URL
            if (count($args) < $method->getNumberOfRequiredParameters()) {
                // Not enough parameters in the URL
                return false;
            }
            
            if (count($args) > ($C = $method->getNumberOfParameters())) {
                // Too many parameters in the URL — pass as many as possible to the function
                $path = array_slice($args, $C);
                $args = array_slice($args, 0, $C);
            }
            
            // Try to call the method and see what kind of response we get
            $result = $method->invokeArgs($context, $args);
            
            if ($result instanceof View\BaseView) {
                return $result;
            }
            elseif (is_callable($result)) {
                // We have a callable to be considered the view. We're done.
                return new View\BoundView($result, $args);
            }
            elseif ($result instanceof Dispatcher) {
                // Delegate to a sub dispatcher
                return $res->resolve(implode('/', $path));
            }
            elseif (is_object($result)) {
                // Recurse forward with the remainder of the path
                return $this->resolve2($result, $path);
            }
            
            // From here, we assume that the call failed
        }
        catch (\ReflectionException $ex) {
            // No such method, try def() or recurse backwards
        }
        catch (Exception\ConditionFailed $ex) {
            // The matched method refused the request — recurse backwards
        }
        
        // Not able to dispatch the method. Try def()
        if ($head != 'def') {
            $path = array_merge(['def'], $original_path);
            return $this->resolve2($context, $path);
        }
        
        // Unable to dispatch the request
        return false;
    }
    
    function reverse($what) {
        // pass
        throw new \Exception();
    }
}