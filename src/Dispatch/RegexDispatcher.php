<?php

namespace Phlite\Dispatch;

use Phlite\Request\HttpResponse;

/**
 * URL resolver and dispatcher. It's meant to be quite lightweight, so the
 * functions aren't separated
 */
class RegexDispatcher
implements Dispatcher {
    
    var $file = false;
    var $urls = false;

    function __construct($file=false) {
        if (is_array($file)) {
            $this->urls = $file;
        }
        else {
            $this->urls = array();
            $this->file = $file;
        }
    }

    function resolve($url, $args=array()) {
        if ($this->file) { $this->lazy_load(); }
        foreach ($this->urls as $route) {
            if ($match = $route->matches($url)) {
                return $match->getView($this, $args);
            }
        }
        throw new Exception\UnsupportedUrl($url . ': Url does not map to a view');
    }
    
    /**
     * Returns the url for the given function and arguments (arguments
     * aren't declared, but will be handled
     */
    function reverse($func, $args=array()) { 
		$r = new Reverser($this);
		return $r->reverse($func, $args);
	}
	
	function getUrls() {
		return $this->urls;
	}
    
    /**
     * Add the url to the list of supported URLs
     */
    function append($url, $prefix=false) {
        if ($prefix) { $url->setPrefix($prefix); }
        array_push($this->urls, $url);
    }
    
    /**
     * Add the urls from another dispatcher onto this one
     */
    function extend($dispatcher) {
        foreach ($dispatcher->urls as $url) { $this->append($url); }
        /* allow inlining / chaining */ return $this;
    }

    static function include_urls($file, $absolute=false, $lazy=true) {
        if (!$absolute) {
            # Fetch the working path of the caller
            $bt = debug_backtrace();
            $file = dirname($bt[0]["file"]) . "/" . $file;
        }
        if ($lazy) return new Dispatcher($file);
        else return (include $file);
    }
    /**
     * The include_urls() method will create a new Dispatcher and set the
     * $this->file to where the file to be loaded is located. When this
     * dispatcher is first accessed, the file will be loaded.
     */
    function lazy_load() {
        $this->extend(include $this->file);
        $this->file=false;
    }
}
