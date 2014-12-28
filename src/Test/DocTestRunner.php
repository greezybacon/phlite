<?php

namespace Phlite\Test;

use Phlite\Cli\Interact;
use Phlite\Io\StringIo;

class DocTestRunner extends Testable {
    
    function setup() {
        $stdout = new StringIo();
        $this->interpreter = new Interact(false, null, $stdout->getResource());
        $this->intro = '';
        $this->prompt1 = $this->prompt2 = '';
    }
    
    function run($test) {
        if ($test->flags & $test::SKIP)
            return true;
        
        $this->setup();
        $this->interpreter->cmdqueue = $test->expression;
        $this->interpreter->cmdloop();
        return $this->interpreter->stdout->getValue();
    }
    
    function runAll(/* Iterable */ $tests) {
        foreach ($tests as $T)
            $this->run($T);
    }
}