<?php

namespace Phlite\Template\Loader;

use Phlite\Project;

class Application
extends BaseLoader {    

    static function getLoader($request) {
        if (!($app = Project::getCurrent()->getCurrentApp()))
            return false;

        $root = $app->getFilesystemRoot();
        $dir = $request->getSettings()->get('TEMPLATE_DIR', 'Templates');
        
        // Add the local application as the root namespace
        $loader = new \Twig_Loader_Filesystem("$root/$dir");

        return $loader;
    }
}
