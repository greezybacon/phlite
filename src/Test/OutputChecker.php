<?php

namespace Phlite\Test;

class OutputChecker {
    
    function checkOutput($expect, $got, $flags) {
        if ($flags & DocTest::NORMALIZE_WHITESPACE) {
            // munge whitespace (newlines included)
            $got = preg_replace('`\s+`', ' ', $got);
            $expect = preg_replace('`\s+`', ' ', $expect);
        }
        if ($flags & DocTest::ELIPSIS) {
            $expect = str_replace('...', "\0", $expect);
            $expect = preg_quote($expect, '`');
            $expect = str_replace("\0", '.*', $expect);
            return preg_match($expect, $got);
        }
        // Trim the whitespace
        list($expect, $got) = preg_replace('`^\s*|\s*$`m', '',
             array($expect, $got));
        return $expect == $got;
    }
    
    function getDifference($expect, $got) {
        // TODO: Employ some difflib magic here
    }
}