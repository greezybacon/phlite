<?php

namespace Phlite\Logging;

class Filterer {
    function __construct() {
        $this->filters = array();
    }

    function addFilter($filter) {
        if (!in_array($filter, $this->filters)) {
            $this->filters[] = $filter;
        }
    }

    function removeFilter($filter) {
        if ($k = array_search($filter, $this->filters)) {
            unset($this->filters[$k]);
        }
    }

    /**
     * Determine if a record is loggable by consulting all the filters.
     *
     * The default is to allow the record to be logged; any filter can veto
     * this and the record is then dropped. Returns a zero value if a record
     * is to be dropped, else non-zero.
     */
    function filter($record) {
        $rv = 1;
        foreach ($this->filters as $f) {
            if (!$f->filter($record)) {
                $rv = 0;
                break;
            }
        }
        return $rv;
    }
}
