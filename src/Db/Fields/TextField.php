<?php

namespace Phlite\Db\Fields;

use Phlite\Db\Connection;

class TextField extends BaseField {

    var $max_length;
    var $collation;
    var $charset;
    
    function to_php($value, Connection $connection) {
        if ($this->charset && $this->charset != $connection->charset) {
            return new Unicode($value, $this->charset);
        }
        return $value;
    }
    
    function getCreateSql($compiler) {
        return sprintf('%s VARCHAR(%s) %s%s%s%s',
            $compiler->quote($this->name),
            $this->max_length,
            ($this->charset) ? ' CHARSET ' . $this->charset : '',
            ($this->collation) ? ' COLLATION ' . $this->collation : '',
            ($this->nullable ? 'NOT ' : '') . 'NULL',
            ($this->default) ? ' DEFAULT ' . $compiler->escape($this->default) : ''
        );
    }
}