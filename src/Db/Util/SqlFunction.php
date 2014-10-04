<?php

namespace Phlite\Db\Util;

class SqlFunction {
    var $alias;

    function SqlFunction($name) {
        $this->func = $name;
        $this->args = array_slice(func_get_args(), 1);
    }

    function toSql($compiler, $model=false, $alias=false) {
        return sprintf('%s(%s)%s', $this->func, implode(',', $this->args),
            $alias && $this->alias ? ' AS '.$compiler->quote($this->alias) : '');
    }

    function setAlias($alias) {
        $this->alias = $alias;
    }

    static function __callStatic($func, $args) {
        $I = new static($func);
        $I->args = $args;
        return $I;
    }
}