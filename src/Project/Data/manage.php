<?php

use Phlite\Cli;
use Phlite\Dispatch;

require __DIR__ . '/vendor/autoload.php';

// Locate and project root
$project = new Phlite\Project();

// Locate and load settings file
$project->loadSettings('settings.php');

if (php_sapi_name() == 'cli') {
    $manager = new Cli\Manager();
    $manager->run($project);
}
else {
    $handler = new Dispatch\Handlers\ApacheHandler();
    $handler();
}