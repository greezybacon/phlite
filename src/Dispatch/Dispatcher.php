<?php

namespace Phlite\Dispatch;

interface Dispatcher {
    
    // Main entry from the handler
    function resolve($url);
    
    // Reverse a URL path from a view or named dispatch endpoint
    function reverse($endpoint);
    
    // Manually register an endpoint (?)
    // function connect($url, $name=false, $endpoint=false);
}