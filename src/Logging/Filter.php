<?php

namespace Phlite\Logging;

/**
 * Filter instances are used to perform arbitrary filtering of LogRecords.
 *
 * Loggers and Handlers can optionally use Filter instances to filter
 * records as desired. The base filter class only allows events which are
 * below a certain point in the logger hierarchy. For example, a filter
 * initialized with "A.B" will allow events logged by loggers "A.B",
 * "A.B.C", "A.B.C.D", "A.B.D" etc. but not "A.BB", "B.A.B" etc. If
 * initialized with the empty string, all events are passed.
 */
class Filter {
    
    function __construct($name='') {
        $this->name = $name;
        $this->nlen = strlen($name);
    }

    /**
     * Determine if the specified record is to be logged.
     *
     * Is the specified record to be logged? Returns 0 for no, nonzero for
     * yes. If deemed appropriate, the record may be modified in-place.
     */
    function filter($record) {
        if ($this->nlen == 0) {
            return 1;
        }
        elseif ($this->name == $record->name) {
            return 1;
        }
        elseif (strpos($this->name) !== 0) {
            return 0;
        }
        return $record->name[$this->nlen] == '.';
    }
}
