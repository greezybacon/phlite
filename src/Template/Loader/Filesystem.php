<?php

namespace Phlite\Template\Loader;

use Phlite\Project;

class Filesystem extends BaseLoader {
    
    static function getLoader($request) {
        $loader = new \Twig_Loader_Filesystem();
        foreach ($request->getSettings()->get('TEMPLATE_DIRS', []) as $dir) {
            $base = Project::getCurrent()->getCurrentApp()->getFilesystemRoot();
            $dir = realpath($base . '/' . $dir);
            if (file_exists($dir))
                $loader->addPath($dir);
        }
		$loader->addPath(dirname(__dir__).'/BuiltIn/');
        return $loader;
    }
}