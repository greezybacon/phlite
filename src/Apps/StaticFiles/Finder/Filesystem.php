<?php

namespace Phlite\Apps\StaticFiles\Finder;

use Phlite\Project;

class Filesystem extends BaseFinder {
    function locate($path) {
        $project = Project::getCurrent();
        foreach ($project->getSettings()->get('STATICFILES_DIRS', []) as $root) {
            $full = "{$root}/{$path}";            
            if (is_file($full)) {
                return $full;
            }
        }
    }
    
    function getIterator() 
        $paths = array();
        $project = Project::getCurrent();
        foreach ($project->getSettings()->get('STATICFILES_DIRS', []) as $root) {
            if (!is_dir($root))
                continue;
            $paths[] = $full;
        }
        return $this->iterPaths($paths);
    }
}