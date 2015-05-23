<?php

namespace Phlite\Apps\StaticFiles;

use Phlite\Request;
use Phlite\Text;

class Middleware extends Request\Middleware {
    
    function processRequest($request) {
        $path = new Text\Bytes($request->getPath());
        
        $static_dir = ltrim(
            $request->getSettings()->get('STATIC_URL', '/static/'), '/');
        if (!$path->startsWith($static_dir))
            return;
            
        $path = $path->substr(strlen($static_dir));
        
        $class = $request->getSettings()->get('STATICFILES_STORAGE');
        $storage = new $class($request);
        return $storage->resolve($path);
    }
}