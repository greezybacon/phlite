<?php

namespace Phlite\Db\Model;

use Phlite\Db\Manager;
use Phlite\Db\Util;

class QuerySet implements \IteratorAggregate, \ArrayAccess {
    var $model;

    var $constraints = array();
    var $ordering = array();
    var $limit = false;
    var $offset = 0;
    var $related = array();
    var $values = array();
    var $defer = array();
    var $annotations = array();
    var $lock = false;

    const LOCK_EXCLUSIVE = 1;
    const LOCK_SHARED = 2;

    var $iterator = 'Phlite\Db\Model\ModelInstanceManager';

    var $params;
    var $query;

    function __construct($model) {
        $this->model = $model;
    }

    function filter() {
        foreach (func_get_args() as $Q) {
            $this->constraints[] = $Q instanceof Util\Q ? $Q : new Util\Q($Q);
        }
        return $this;
    }

    function exclude() {
        foreach (func_get_args() as $Q) {
            $this->constraints[] = $Q instanceof Util\Q ? $Q->negate() : Util\Q::not($Q);
        }
        return $this;
    }

    function defer() {
        foreach (func_get_args() as $f)
            $this->defer[$f] = true;
        return $this;
    }

    function order_by() {
        $this->ordering = array_merge($this->ordering, func_get_args());
        return $this;
    }

    function lock($how=false) {
        $this->lock = $how ?: self::LOCK_EXCLUSIVE;
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
        // This disables related models
        $this->related = false;
        return $this;
    }

    function values_flat() {
        $this->values = func_get_args();
        $this->iterator = 'FlatArrayIterator';
        // This disables related models
        $this->related = false;
        return $this;
    }

    function all() {
        return $this->getIterator()->asArray();
    }

    function first() {
        $this->limit(1);
        return $this[0];
    }

    function one() {
        $list = $this->all();
        if (count($list) == 0)
            throw new Exception\DoesNotExist();
        elseif (count($list) > 1)
            // TODO: Throw error if more than one result from database
            throw new Exception\NotUnique('One object was expected; however '
                .'multiple objects in the database matched the query. '
                .sprintf('In fact, there are %d matching objects.', count($list))
            );
        return $list[0];
    }

    function count() {
        $connection = Manager::getConnection($this->model);
        $compiler = $this->getCompiler();
        $stmt = $compiler->compileCount($this);
        $exec = $connection->execute($stmt);
        $row = $exec->fetchRow();
        return $row[0];
    }

    function exists($fetch=false) {
        if ($fetch) {
            return (bool) $this[0];
        }
        return $this->count() > 0;
    }

    function annotate($annotations) {
        if (!is_array($annotations))
            $annotations = func_get_args();
        foreach ($annotations as $name=>$A) {
            if ($A instanceof Util\Aggregate) {
                if (is_int($name))
                    $name = $A->getFieldName();
                $A->setAlias($name);
                $this->annotations[$name] = $A;
            }
        }
        return $this;
    }
    
    protected function getCompiler() {
        $connection = Manager::getConnection($this->model);
        return $connection->getCompiler();
    }

    function delete() {
        $connection = Manager::getConnection($this->model);
        $compiler = $connection->getCompiler();
        // XXX: Mark all in-memory cached objects as deleted
        $stmt = $compiler->compileBulkDelete($this);
        $exec = $connection->execute($stmt);
        return $exec->affected_rows();
    }

    function update(array $what) {
        $connection = Manager::getConnection($this->model);
        $compiler = $connection->getCompiler();
        $stmt = $compiler->compileBulkUpdate($this, $what);
        $exec = $connection->execute($stmt);
        return $exec->affected_rows();
    }

    function __clone() {
        unset($this->_iterator);
        unset($this->query);
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
        throw new \Exception(__('QuerySet is read-only'));
    }
    function offsetSet($a, $b) {
        throw new \Exception(__('QuerySet is read-only'));
    }

    function __toString() {
        return (string) $this->getQuery();
    }

    function getQuery($options=array()) {
        if (isset($this->query))
            return $this->query;

        // Load defaults from model
        $model = $this->model;
        $model::_inspect();
        if (!$this->ordering && isset($model::$meta['ordering']))
            $this->ordering = $model::$meta['ordering'];
        if (!$this->related && $model::$meta['select_related'])
            $this->related = $model::$meta['select_related'];
        if (!$this->defer && $model::$meta['defer'])
            $this->defer = $model::$meta['defer'];

        $this->query = $this->getCompiler()->compileSelect($this);

        return $this->query;
    }
}