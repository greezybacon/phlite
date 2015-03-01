<?php

namespace Phlite\Apps\Db\Cli;

use Phlite\Cli;
use Phlite\Db;

class Shell extends Cli\Module {
    
    function run($args, $options) {
        $interactive = new Cli\Interact();
        
        // TODO: Initialize database connections from settings, etc.
        
        $interactive->cmdloop();
    }
}