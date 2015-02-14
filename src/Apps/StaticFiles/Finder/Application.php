<?php

namespace Phlite\Apps\StaticFiles\Finder;

use Phlite\Project;

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
        $files = array();
        $project = Project::getCurrent();
        foreach ($project->getApplications() as $A) {
            $root = $A->getFilesystemRoot();
            $full = "{$root}/Static";
            $relative = strlen($full)+1;
            if (!is_dir($full))
                continue;
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($full, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($it as $F) {
                if (!$F->isFile())
                    continue;
                $files[] = [$F, substr($F, $relative)];
            }
        }
        return new \ArrayIterator($files);
    }
}