<?php

namespace Phlite\Db\Util;

class Field
extends Expression {
    function __construct($field) {
        $this->field = $field;
    }

    function toSql($compiler, $model=false, $alias=false) {
        list($field) = $compiler->getField($this->field, $model);
        return $field;
    }
}
