<?php

namespace Phlite\Test;

use Phlite\Util\ListObject;

/**
 * The inspector searches through class docstrings for doc tests. For doc
 * tests found, a DocTest instance is set up to perform and verify the test
 */
class DoctestLoader extends TestLoader {
    
    protected $pattern;
    protected $recurse;
    
    function __construct($glob=false, $recurse=false) {
        $this->pattern = $glob;
        $this->recurse = $recurse;
    }
    
    function getTests() {
        return $this->findAllTests($this->pattern, $this->recurse)
    }
    
    function findAllTests($start, $recurse=false) {
        $tests = new ListObject();
        foreach (glob($start) as $f) {
            $tests[] = $this->findTests(file_get_contents($f));
        }
        if ($recurse) {
            foreach (glob(dirname($start).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
                $tests->extend(
                    $this->findAllTests($dir.'/'.basename($start), $recurse-1));
            }
        }
        $tests->extend($more);
        return $tests;         
    }
    
    function findTests($source) {
        // Let PHP do the work
        $tokens = token_get_all($source);
        $tests = array();
        while (list($i, $t) = each($tokens)) {
            switch ($t[0]) {
            case T_DOC_COMMENT:
                // Strip leading chars and whitespace
                $code = preg_replace('`^\s+\*\s*`', ''. $t[1]);
                // Identify the expression and result
                $lines = preg_split('`\r|\r\n|\n`', $code);
                $expr = $result = array();
                $stop = false;
                while (!$stop) {
                    while (list($i, $l) = each($lines)) {
                        if (!preg_match('`^>>>|^\.\.\.`', $l))
                            break;
                        $expr[] = $l;
                    }
                    while (list($i, $l) = each($lines)) {
                        if (preg_match('`^>>>|^\.\.\.|^\s*$`', $l))
                            break;
                        $result[] = $l;
                    }
                    $stop = ! (bool) $result;
                    if ($expr && $result) {
                        $tests[] = new DocTest($expr,
                            implode("\n", $result));
                        $expr = $result = array();
                    }
                }
            }
        }
        return $tests;
    }
}