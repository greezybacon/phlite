<?php

namespace Phlite\Db\Util;

class Aggregate extends SqlFunction {

    var $func;
    var $expr;
    var $distinct=false;
    var $constraint=false;

    function __construct($func, $expr, $distinct=false, $constraint=false) {
        $this->func = $func;
        $this->expr = $expr;
        $this->distinct = $distinct;
        if ($constraint instanceof Q)
            $this->constraint = $constraint;
        elseif ($constraint)
            $this->constraint = new Q($constraint);
    }

    static function __callStatic($func, $args) {
        $distinct = @$args[1] ?: false;
        $constraint = @$args[2] ?: false;
        return new static($func, $args[0], $distinct, $constraint);
    }

    function toSql($compiler, $model=false, $alias=false) {
        $options = array('constraint'=>$this->constraint);
        list($field) = $compiler->getField($this->expr, $model, $options);
        return sprintf('%s(%s)%s', $this->func, $field,
            $alias && $this->alias ? ' AS '.$compiler->quote($this->alias) : '');
    }

    function getFieldName() {
        return strtolower(sprintf('%s__%s', $this->args[0], $this->func));
    }
}
