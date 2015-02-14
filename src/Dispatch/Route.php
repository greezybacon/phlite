<?php

namespace Phlite\Dispatch;

class Route {
    // Flag constants
    const APPLICATION = 0x0001;         // sub-dispatch application routes
    
	var $capture_groups;
    var $prefix = false;
    var $regex;
    var $target;
    var $args;
    var $flags;
	
    function __construct($regex, $view, $args=array(), $flags=false) {
        # Add the slashes for the Perl syntax
        $this->regex = $regex;
        $this->target = $view;
        $this->args = $args ?: array();
        $this->flags = $flags;
    }

    function setPrefix($prefix) { 
        $this->prefix = $prefix; 
    }

    function matches($url) {
        $matches = array();
        if (preg_match('@'.$this->regex.'@', $url, $matches) == 1) {
            $args = array();
            // XXX: Perhaps we should keep all the args and actually attempt named argument invocation of the target
            for ($i=1; ; $i++) {
                if (isset($matches[$i]))
                    $args[] = $matches[$i];
                else
                    break;
            }
            return new Dispatchable($this, $url, array_merge($args, $this->args),
                substr($url, strlen($matches[0])));
        }
        return false;
    }

	function getTarget() {
		return $this->target;
	}
	
    /**
     * Used in the View reversing process to retrieve the URL text for this
     * route given the optional arguments which would be passed to the View
     */
	function getUrl($args=null) {
        // TODO: Strip regex match pieces such as ^ and $ and such
		if (!isset($args))
			return $this->regex;
		
		return preg_replace_callback('`(?<!\\\\)\((?!\?:)(?:[^)]+|\\\\\))+(?<!\\\\)\)`',
			function($matches) use (&$args) {
				return array_shift($args) ?: '';
			}, $this->regex);
	}
	
	function matchesArgs($args) {
		foreach ($this->_getCaptureGroups() as $i=>$g) {
			if (!preg_match($g, $args[$i]))
				return false;
		}
		return true;
	}
	
	protected function _getCaptureGroups() {
		if (!isset($this->capture_groups)) {
			$this->capture_groups = array();
			if (preg_match('`(?<!\\\\)\((?!\?:)(?:[^)]+|\\\\\))+(?<!\\\\)\)`',
			 		$this->regex, $this->capture_groups))
				array_shift($this->capture_groups);
		}
		return $this->capture_groups;
	}
}
