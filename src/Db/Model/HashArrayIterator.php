<?php

namespace Phlite\Db\Model;

class HashArrayIterator extends ResultSet {
    function fillTo($index) {
        while ($this->resource && $index >= count($this->cache)) {
            if ($row = $this->resource->fetchArray()) {
                $this->cache[] = $row;
            } else {
                $this->resource->close();
                $this->resource = null;
                break;
            }
        }
    }
}