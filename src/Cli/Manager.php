<?php

namespace Phlite\Cli;

use Phlite\Project;
use Phlite\Util;

/**
 * Class: Manager
 *
 * This is the application that interacts directly with the registered
 * Module classes and the User via the CLI. It has the capability to
 * interact with the Project, associated Applications and settings, and
 * automatically handle the command-line options, instanciate and
 * configure the respective Cli module and get the Module::run() method
 * underway.
 */
class Manager
extends Module {
    
    var $prologue =
        "Manage one or more osTicket installations";

    var $arguments = array(
        'module' => array(
            'help' => "App cli module to be executed"
        ),
    );
    
    var $autohelp = false;
    
    function __construct($project) {
        parent::__construct();
    }
    
    function __invoke() {
        $this->_run();
    }
    
    function findInstalledCliApps() {
        $discovered = new Util\ArrayObject();
        $apps = Project::getCurrent()->getApplications();
        foreach ($apps as $A) {
            foreach ($A->getCliModules() as $mod) {
                // TODO: Error if there is already a mod under this name
                $section = $discovered->setDefault($A->getName(), new Util\ArrayObject());
                $section[$A->getLabel().':'.$mod->getName()] = $mod;
            }
        }
        return $discovered;
    }
    
    function run($args, $options) {
        $this->parseOptions();
        
        if ($options['help'] && !$args['module']) {
            return $this->showHelp();
        }
        
        $module = $args['module'];

        global $argv;
        foreach ($argv as $idx=>$val)
            if ($val == $module)
                unset($argv[$idx]);
        
        $apps = $this->findInstalledCliApps();
        
        foreach ($apps as $A => $mods) {
            foreach ($mods as $name => $M) {
                if (strcasecmp($name, $module) === 0)
                    return $M->_run($args['module']);
            }
        }

        $this->stderr->write("Unknown action given\n");
        $this->showHelp();
    }

    function showHelp() {
        $apps = $this->findInstalledCliApps();
        
        foreach ($apps as $app_name => $app_mods) {
            foreach ($app_mods as $name => $mod) {
                $this->arguments['module']['options'][$name] = '...';
            }
        }

        $this->epilog =
            "Currently available modules follow. Use 'manage.php <module>
            --help' for usage regarding each respective module:";

        parent::showHelp();

        echo "\n";
    }
}