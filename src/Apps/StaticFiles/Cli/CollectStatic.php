<?php

namespace Phlite\Apps\StaticFiles\Cli;

use Phlite\Cli;

class CollectStatic extends Cli\Module {    
    function run($args, $options) {
        $this->stdout->writeline('Hello');
    }
}