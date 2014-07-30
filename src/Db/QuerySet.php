<?php

namespace Phlite\Db;

class QuerySet implements IteratorAggregate, ArrayAccess {
    var $model;

    var $constraints = array();
    var $exclusions = array();
    var $ordering = array();
    var $limit = false;
    var $offset = 0;
    var $related = array();
    var $values = array();

    var $compiler = 'MySqlCompiler';
    var $iterator = 'ModelInstanceIterator';

    var $params;
    var $query;

    function __construct($model) {
        $this->model = $model;
    }

    function filter() {
        // Multiple arrays passes means OR
        $this->constraints[] = func_get_args();
        return $this;
    }

    function exclude() {
        $this->exclusions[] = func_get_args();
        return $this;
    }

    function order_by() {
        $this->ordering = array_merge($this->ordering, func_get_args());
        return $this;
    }

    function limit($count) {
        $this->limit = $count;
        return $this;
    }

    function offset($at) {
        $this->offset = $at;
        return $this;
    }

    function select_related() {
        $this->related = array_merge($this->related, func_get_args());
        return $this;
    }

    function values() {
        $this->values = func_get_args();
        $this->iterator = 'HashArrayIterator';
        return $this;
    }

    function values_flat() {
        $this->values = func_get_args();
        $this->iterator = 'FlatArrayIterator';
        return $this;
    }

    function all() {
        return $this->getIterator()->asArray();
    }

    function count() {
        $class = $this->compiler;
        $compiler = new $class();
        return $compiler->compileCount($this);
    }

    function exists() {
        return $this->count() > 0;
    }

    // IteratorAggregate interface
    function getIterator() {
        $class = $this->iterator;
        if (!isset($this->_iterator))
            $this->_iterator = new $class($this);
        return $this->_iterator;
    }

    // ArrayAccess interface
    function offsetExists($offset) {
        return $this->getIterator()->offsetExists($offset);
    }
    function offsetGet($offset) {
        return $this->getIterator()->offsetGet($offset);
    }
    function offsetUnset($a) {
        throw new Exception('QuerySet is read-only');
    }
    function offsetSet($a, $b) {
        throw new Exception('QuerySet is read-only');
    }

    function __toString() {
        return (string)$this->getQuery();
    }

    function getQuery($options=array()) {
        if (isset($this->query))
            return $this->query;

        // Load defaults from model
        $model = $this->model;
        if (!$this->ordering && isset($model::$meta['ordering']))
            $this->ordering = $model::$meta['ordering'];

        $class = $this->compiler;
        $compiler = new $class($options);
        $this->query = $compiler->compileSelect($this);

        return $this->query;
    }
}
