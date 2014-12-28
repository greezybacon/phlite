<?php

namespace Phlite\Dispatch;

use Phlite\Project;

class Reverser {
	var $dispatcher;
	
	function __construct($dispatcher=null) {
		$this->dispatcher = $dispatcher;
	}
	
    /**
     * Retrieve a URL for a view. Since the Dispatcher type is used to
     * adapt an HTTP request to a View, this Reverse can be used to find
     * the URL that could be used to trigger the View in a future request.
     * This paradigm assists in the DRY pattern, so that refactoring the
     * URL scheme of your Application does not affect the application
     * itself
     */
	function reverse($view, $args=array(), $method=null, $application=null) {
		$dispatcher = $this->dispatcher;
        $project = Project::getCurrent();
		if (!$dispatcher) {
			$dispatcher = $project->getApplication($application);
		}
        $applications = $project->getApplications();
		foreach ($dispatcher->getUrls() as $route) {
            $target = $route->getTarget();

            // Isolate the namespace portion of a FQ class name
            $namespace = explode('\\', $target);
            array_pop($namespace);
            $namespace = implode('\\', $namespace);

            // TODO: getTarget() may be a view which may need to be
            // inspected. Assume namespaces will coincide
            if (isset($applications[$namespace])) {
                try {
                    $app = $applications[$namespace];
                    $reverser = new Reverser($app);
                    return $reverser->reverse($view, $args, $method);
                } catch (Route404 $ex) {
                    // Fall through
                }
            }
            elseif ($route->getTarget() != $view) {
                continue;
            }
            elseif (!$args || ($args && $route->matchesArgs($args))) {
                // TODO: Consider URL path from recursed reversing. It
                // should be prepended.
                return $route->getUrl($args);
            }
		}
		throw new Exception\Route404($view . ': No reverse found with supplied args');
	}
}
