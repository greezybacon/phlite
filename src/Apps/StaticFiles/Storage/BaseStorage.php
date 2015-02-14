<?php

namespace Phlite\Apps\StaticFiles\Storage;

use Phlite\Project;

abstract class BaseStorage {
    
    function __construct($request) {}
    
    function resolve($path) { 
        foreach ($this->getFinders() as $F) {
            if ($located = $F->locate($path)) {
                return $this->serve($located, $path);
            }
        }
    }
        
    function getFinders() {
        $settings = Project::getCurrent()->getSettings();
        $finders = $settings->get('STATICFILES_FINDERS', []);
        foreach ($finders as $idx => $F) {
            if (is_string($F))
                $finders[$idx] = new $F();
        }
        return $finders;
    }
    
    /**
     * serve
     *
     * Request the storage backend to produce an HTTP response to serve the
     * static file represented by the path. This is generally only used for
     * development as the files should be collected and served by the HTTP
     * server in production.
     */
    abstract function serve($full, $base);
        
    /**
     * store
     *
     * Request the storage backend to transfer the application path
     * (absolute) to the location where they are to be stored
     */
    abstract function store($path, $base_path);
    
}