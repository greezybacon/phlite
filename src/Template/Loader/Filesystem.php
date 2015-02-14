<?php

namespace Phlite\Template\Loader;

use Phlite\Project;

class Filesystem extends BaseLoader {
    
    static function getLoader($request) {
        $loader = new \Twig_Loader_Filesystem();
        foreach ($request->getSettings()->get('TEMPLATE_DIRS', []) as $dir) {
            $dir = realpath($dir);
            if (is_dir($dir))
                $loader->addPath($dir);
        }
		$loader->addPath(dirname(__dir__).'/BuiltIn/');
        return $loader;
    }
}