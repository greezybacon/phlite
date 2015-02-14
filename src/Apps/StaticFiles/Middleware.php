<?php

namespace Phlite\Apps\StaticFiles;

use Phlite\Request;
use Phlite\Text;

class Middleware extends Request\Middleware {
    
    function processRequest($request) {
        $path = new Text\String($request->getPath());
        
        $static_dir = $request->getSettings()->get('STATIC_URL', '/static/');
        if (!$path->startsWith($static_dir))
            return;
            
        $path = $path->slice(strlen($static_dir));
        
        $class = $request->getSettings()->get('STATICFILES_STORAGE');
        $storage = new $class($request);
        return $storage->resolve($path);
    }
}