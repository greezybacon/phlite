<?php

namespace Phlite\Apps\StaticFiles\Finder;

use Phlite\Project;

/**
 * Class: Application
 *
 * Finds static files in a 'Static/' folder inside each installed
 * application.
 */
class Application extends BaseFinder {
    
    function locate($path) {
        $project = Project::getCurrent();
        foreach ($project->getApplications() as $A) {
            $root = $A->getFilesystemRoot();
            $full = "{$root}/Static/{$path}";            
            if (is_file($full)) {
                return $full;
            }
        }
    }
    
    function getIterator() {
        $paths = array();
        $project = Project::getCurrent();
        foreach ($project->getApplications() as $A) {
            $root = $A->getFilesystemRoot();
            $full = "{$root}/Static";
            if (!is_dir($full))
                continue;
            $paths[] = $full;
        }
        return $this->iterPaths($paths);
    }
}