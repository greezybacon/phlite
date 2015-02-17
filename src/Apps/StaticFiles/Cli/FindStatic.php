<?php

namespace Phlite\Apps\StaticFiles\Cli;

use Phlite\Cli;
use Phlite\Project;

class FindStatic extends Cli\Module {
    
    function run($args, $options) {
        $project = Project::getCurrent();
        $root = $project->getFilesystemRoot() . '/';
        $settings = $project->getSettings();
        $static_url = $settings->get('STATIC_URL');
        foreach ($settings->get('STATICFILES_FINDERS') as $F) {
            if (is_string($F))
                $F = new $F();
            foreach ($F as $file) {
                $file = $file->getFullPath();
                if (strpos($file, $root) !== false) {
                    $file = '(project)/' . substr($file, strlen($root));
                }
                $this->stderr->writeline($file);
            }
        }
    }
}