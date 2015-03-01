<?php

namespace Phlite\Db\Util;

class Expression {
    var $alias;
    
    function __construct($args) {
        $this->args = $args;
    }

    function toSql($compiler, $model=false, $alias=false) {
        $O = array();
        foreach ($this->args as $field=>$value) {
            list($field, $op) = $compiler->getField($field, $model);
            if (is_callable($op))
                $O[] = call_user_func($op, $field, $value, $model);
            else
                $O[] = sprintf($op, $field, $compiler->input($value));
        }
        return implode(' ', $O) . ($alias ? ' AS ' . $alias : '');
    }
    
    // Allow $function->plus($something)
    function __call($operator, $operands) {
        return BinaryExpression::__callStatic($operator, $operands);
    }
}