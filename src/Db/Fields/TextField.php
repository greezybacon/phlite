<?php

namespace Phlite\Db\Fields;

use Phlite\Db\Connection;

class TextField extends BaseField {

    var $max_length;
    var $collation;
    var $charset;
    
    function to_php($value, Connection $connection) {
        if ($this->charset && $this->charset != $connection->charset) {
            $u = new Unicode($value, $connection->charset);
            $u->decode($this->charset);
            return $u;
        }
        return $value;
    }
}