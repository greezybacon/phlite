<?php

namespace Phlite\Db\Model;

class InstrumentedList
extends ModelInstanceManager
implements \JsonSerializable {
    var $key;
    var $id;
    var $model;

    function __construct($fkey, $queryset=false) {
        list($model, $this->key, $this->id) = $fkey;
        if (!$queryset)
            $queryset = $model::objects()->filter(array($this->key=>$this->id));
        parent::__construct($queryset);
        $this->model = $model;
        if (!$this->id)
            $this->resource = null;
    }

    function add($object, $at=false) {
        if (!$object || !$object instanceof $this->model)
            throw new Exception\OrmError(__('Attempting to add invalid object to list'));

        $object->set($this->key, $this->id);
        $object->save();

        if ($at !== false)
            $this->cache[$at] = $object;
        else
            $this->cache[] = $object;
    }
    function remove($object) {
        $object->delete();
        // XXX: Delete from local cache
    }

    function reset() {
        $this->cache = array();
    }

    // QuerySet delegates
    function count() {
        return $this->queryset->count();
    }
    function exists() {
        return $this->queryset->exists();
    }
    function expunge() {
        if ($this->queryset->delete())
            $this->reset();
    }
    function update(array $what) {
        return $this->queryset->update($what);
    }

    // Fetch a new QuerySet — ensure local queryset object is not modified
    function objects() {
        return clone $this->queryset;
    }

    function offsetUnset($a) {
        $this->fillTo($a);
        $this->cache[$a]->delete();
    }
    function offsetSet($a, $b) {
        $this->fillTo($a);
        $this->cache[$a]->delete();
        $this->add($b, $a);
    }

    // QuerySet overriedes
    function __call($func, $args) {
        return call_user_func_array(array($this->objects(), $func), $args);
    }
    
    // ---- JsonSerializable interface ------------------------
    function jsonSerialize() {
        return $this->queryset->asArray();
    }
}