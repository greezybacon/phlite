<?php

namespace Phlite\Dispatch;

class Route {
    function __construct($regex, $view, $args=false, $method=false) {
        # Add the slashes for the Perl syntax
        $this->regex = "@" . $regex . "@";
        $this->func = $view;
        $this->args = ($args) ? $args : array();
        $this->prefix = false;
        $this->method = $method;
    }

    function setPrefix($prefix) { $this->prefix = $prefix; }

    function matches($url) {
        if ($this->method && $_SERVER['REQUEST_METHOD'] != $this->method) {
            return false;
        }
        return preg_match($this->regex, $url, $this->matches) == 1;
    }

    function dispatch($url, $prev_args=null) {

        # Remove named values from the match array
        $f = array_filter(array_keys($this->matches), 'is_numeric');
        $this->matches = array_intersect_key($this->matches, array_flip($f));

        if (@get_class($this->func) == "Dispatcher") {
            # Trim the leading match off the $url and call the
            # sub-dispatcher. This will be the case for lines in the URL
            # file like
            # url("^/blah", Dispatcher::include_urls("blah/urls.conf.php"))
            # Also, pass arguments matched so far (if any) to the receiving
            # resolve() method by merging the $prev_args into $this->matches
            # (excluding $this->matches[0], which is the matched URL at this
            # level)
            return $this->func->resolve(
                substr($url, strlen($this->matches[0])),
                array_merge(($prev_args) ? $prev_args : array(),
                    array_slice($this->matches, 1)));
        }

        # Drop the first item of the matches array (which is the whole
        # matched url). Then merge in any initial arguments.
        unset($this->matches[0]);

        # Prepend received arguments (from a parent Dispatcher). This is
        # different from the static args, which are postpended
        if (is_array($prev_args))
            $args = array_merge($prev_args, $this->matches);
        else $args = $this->matches;
        # Add in static args specified in the constructor
        $args = array_merge($args, $this->args);
        # Apply the $prefix given
        list($class, $func) = $this->apply_prefix();
        if ($class) {
            # Create instance of the class, which is the first item,
            # then call the method which is the second item
            $func = array(new $class, $func);
        }

        if (!is_callable($func))
            Http::response(500, 'Dispatcher compile error. Function not callable');

        return call_user_func_array($func, $args);
    }
    /**
     * For the $prefix recieved by the constuctor, prepend it to the
     * received $class, if any, then make an import if necessary. Lastly,
     * return the appropriate $class, and $func that should be invoked to
     * dispatch the URL.
     */
    function apply_prefix() {
        if (is_array($this->func)) { list($class, $func) = $this->func; }
        else { $func = $this->func; $class = ""; }
        if (is_object($class))
            return array(false, $this->func);
        if ($this->prefix)
            $class = $this->prefix . $class;

        if (strpos($class, ":")) {
            list($file, $class) = explode(":", $class, 2);
            include $file;
        }
        return array($class, $func);
    }

    static function any($regex, $func, $args=false, $method=false) {
        return new static($regex, $func, $args, $method);
    }

    static function post($regex, $func, $args=false) {
        return new static ($regex, $func, $args, "POST");
    }

    static function get($regex, $func, $args=false) {
        return new static ($regex, $func, $args, "GET");
    }

    static function put($regex, $func, $args=false) {
        return new static ($regex, $func, $args, "PUT");
    }

    static function delete($regex, $func, $args=false) {
        return new static ($regex, $func, $args, "DELETE");
    }
}
