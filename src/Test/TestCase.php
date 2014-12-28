<?php

namespace Phlite\Test;

class TestCase extends Testable {
    
    function run() {
        $rc = new \ReflectionClass(get_class($this));
        foreach ($rc->getMethods() as $m) {
            if (stripos($m->name, 'test') === 0) {
                $this->setup();
                call_user_func(array($this, $m->name));
                $this->teardown();
            }
        }
    }
    
    static function line_number_for_offset($filename, $offset) {
        $lines = file($filename);
        $bytes = $line = 0;
        while ($bytes < $offset) {
            $bytes += strlen(array_shift($lines));
            $line += 1;
        }
        return $line;
    }
}