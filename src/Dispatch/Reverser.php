<?php

namespace Phlite\Dispatch;

use Phlite\Project;

class Reverser {
	var $dispatcher;
	
	function __construct($dispatcher=null) {
		$this->dispatcher = $dispatcher;
	}
	
	function reverse($view, $args=array(), $method=null, $application=null) {
		$dispatcher = $this->dispatcher;
		if (!$dispatcher) {
			$project = Project::currentProject();
			$dispatcher = $project->getApplication($application);
		}
		foreach ($dispatcher->getUrls() as $route) {
			if ($route->getTarget() == $view) {
				// TODO: getTarget() may be a view which may need to be inspected. Assume namespaces will coincide
			 	if (!$args || ($args && $route->matchesArgs($args)))
					return $route->getUrl($args);
			}
            elseif ($)
		}
		throw new Exception\Route404($view . ': No reverse found with supplied args');
	}
}