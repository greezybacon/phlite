<?php

namespace Phlite\Logging;

/**
 * A formatter suitable for formatting a number of records
 */
class BufferingFormatter {

    static $defaultFormatter;

    function __construct($linefmt) {
        if ($linefmt) {
            $this->linefmt = $linefmt;
        }
        else {
            $this->linefmt = static::$defaultFormatter;
        }
    }
}

BufferingFormatter::$defaultFormatter = new Phlite\Logging\Formatter();
