<?php

namespace Phlite\Db\Util;

class Func
extends Expression {
    
    function __construct($name) {
        $this->func = $name;
        parent::__construct(array_slice(func_get_args(), 1));
    }

    function toSql($compiler, $model=false, $alias=false) {
        return sprintf('%s(%s)%s', $this->func, implode(',', $this->args),
            $alias && $this->alias ? ' AS '.$compiler->quote($this->alias) : '');
    }

    function setAlias($alias) {
        $this->alias = $alias;
    }
    function getAlias() {
        return $this->alias;
    }

    static function __callStatic($func, $args) {
        $I = new static($func);
        $I->args = $args;
        return $I;
    }
}