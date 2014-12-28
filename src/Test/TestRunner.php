<?php

namespace Phlite\Test;

use Phlite\Io\OutputStream;

class TestRunner implements TestReportOutput {
    
    function __construct() {
        $this->output = new OutputStream('php://stdout');
        $this->loader = new TestLoader();
        $this->reporter = $this;
    }
    
    function run() {
        $this->runTests($this->loader->findTests());
    }
    
    function runTests($tests) {
        foreach ($this->loader->findTests() as $T) {
            try {
                $T->run($this);
                $this->reportPass($T);
            }
            catch (Fail $f) {
                $this->reportFail($f, $T);
            }
            catch (Warning $w) {
                $this->reportWarning($w, $T);
            }
        }
    }
    
    function reportPass($test) {
        
    }
    function reportFail($fail, $test) {
        
    }
    function reportWarning($warning, $test) {
        
    }
}