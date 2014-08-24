<?php

namespace Phlite\Template\Loader;

class Application
extends BaseLoader {    

    static function getLoader($request) {
        if (!($app = $request->getApplication()))
            return false;

        $root = $app->getFilesystemRoot();
        $dir = $app->getTemplateDir();
        // Add the local application as the root namespace
        $loader = new Twig_LoaderFilesystem("$root/$dir");

        foreach ($app->getProject()->getApplications() as $app) {            
            if (file_exists("$root/$dir"))
                $loader->addPath("$root/$dir", $app->getNamespace());
        }
        return $loader;
    }
}
