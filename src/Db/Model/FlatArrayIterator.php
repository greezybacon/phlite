<?php

namespace Phlite\Db;

class FlatArrayIterator extends ResultSet {
    function __construct($queryset) {
        $this->resource = $queryset->getQuery();
    }
    function fillTo($index) {
        while ($this->resource && $index >= count($this->cache)) {
            if ($row = $this->resource->getRow()) {
                $this->cache[] = $row;
            } else {
                $this->resource->close();
                $this->resource = null;
                break;
            }
        }
    }
}