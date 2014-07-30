<?php

namespace Phlite\Dispatch;

/**
 * URL resolver and dispatcher. It's meant to be quite lightweight, so the
 * functions aren't separated
 */
class Dispatcher {
    
    var $file = false;
    var $urls = false;

    function Dispatcher($file=false) {
        if (is_array($file)) {
            $this->urls = $file;
        }
        else {
            $this->urls = array();
            $this->file = $file;
        }
    }

    function resolve($url, $args=null) {
        if ($this->file) { $this->lazy_load(); }
        # Support HTTP method emulation with the _method GET argument
        if (isset($_GET['_method'])) {
            $_SERVER['REQUEST_METHOD'] = strtoupper($_GET['_method']);
            unset($_GET['_method']);
        }
        foreach ($this->urls as $matcher) {
            if ($matcher->matches($url)) {
                return $matcher->dispatch($url, $args);
            }
        }
        Http::response(400, "URL not supported");
    }
    /**
     * Returns the url for the given function and arguments (arguments
     * aren't declared, but will be handled
     */
    function reverse($func) { }
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

    /* static */ function include_urls($file, $absolute=false, $lazy=true) {
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
