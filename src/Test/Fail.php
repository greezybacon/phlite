<?php

namespace Phlite\Test;

/**
 * Fail
 *
 * Simple data class to represent information about a failed test. The output
 * might be sent to the screen or an HTML report, and so the representation
 * of this data is left up to a higher medium.
 */
class Fail {
    
    // Test information
    var $test_suite;
    var $test_name;
    
    // Source info
    var $file;
    var $line;
    var $char;
    var $function;
    
    // Detailed info
    var $message;
    
    function __construct(array $info) {
        foreach ($info as $name=>$val) {
            $this->{$name} = $val;
        }
    }
}