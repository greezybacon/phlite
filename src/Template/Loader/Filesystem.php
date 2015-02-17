<?php

namespace Phlite\Template\Loader;

use Phlite\Project;

class Filesystem extends BaseLoader {
    
    static function getLoader($request) {
        $loader = new \Twig_Loader_Filesystem();
        $project = Project::getCurrent();
        foreach ($project->getSetting('TEMPLATE_DIRS', []) as $dir) {
            $dir = realpath($dir);
            if (is_dir($dir))
                $loader->addPath($dir);
        }
		$loader->addPath(dirname(__dir__).'/BuiltIn/');
        return $loader;
    }
}