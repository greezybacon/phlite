<?php

namespace Phlite\Dispatch;

class Route {
	var $capture_groups;
	
    function __construct($regex, $view, $args=false) {
        # Add the slashes for the Perl syntax
        $this->regex = "@" . $regex . "@";
        $this->target = $view;
        $this->args = ($args) ? $args : array();
        $this->prefix = false;
    }

    function setPrefix($prefix) { $this->prefix = $prefix; }

    function matches($url) {
        $matches = array();
        if (preg_match($this->regex, $url, $matches) == 1) {
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
	
	function getUrl($args=null) {
		if (!isset($args))
			return $this->url;
		
		$copy = $args;
		return preg_replace_callback('`(?<!\\)\((?!\?:)(?:[^)]+|\\\))+(?<!\\)\)`',
			function($matches) use (&$copy) {
				return array_shift($copy) ?: '';
			}, $this->url);
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
			if (preg_match('`(?<!\\)\((?!\?:)(?:[^)]+|\\\))+(?<!\\)\)`',
			 		$this->regex, $this->capture_groups))
				array_shift($this->capture_groups);
		}
		return $this->capture_groups;
	}
}
