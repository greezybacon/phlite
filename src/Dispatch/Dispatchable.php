<?php
    
namespace Phlite\Dispatch;

use Phlite\Dispatch\Exception\DispatchException;
use Phlite\Project\Application;
use Phlite\View\BoundView;

class Dispatchable {
 
    var $url_matched;
    var $url_remaining;
    
    function __construct($route, $url, $args=null, $leftover=false) {
        $this->route = $route;
        $this->url_matched = $url;
        $this->args = $args;
        $this->url_remaining = $leftover;
    } 
    
    function getView($dispatcher, $args=array()) {
        // Add static args to the end of the args list
        $args = array_merge($this->args, $args);

        $target = $this->route->getTarget();
		if (is_string($target)) {
			$target = new $target();
		}

        if ($target instanceof Application) {
            return $target->resolve($this->url_remaining, $args);
		}
        elseif (!is_callable($target))
            throw new DispatchException('Dispatched view is not callable');
        
        return new BoundView($target, $args);
    }
    
}