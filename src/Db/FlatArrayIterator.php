<?php

namespace Phlite\Db;

use Phlite\Db\ModelInstanceIterator;

class FlatArrayIterator extends ModelInstanceIterator {
    function __construct($queryset) {
        $this->resource = $queryset->getQuery();
    }
    function fillTo($index) {
        while ($this->resource && $index >= count($this->cache)) {
            if ($row = $this->resource->getRow()) {
                $this->cache += $row;
            } else {
                $this->resource->close();
                $this->resource = null;
                break;
            }
        }
    }
}
