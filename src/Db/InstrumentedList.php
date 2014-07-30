<?php

namespace Phlite\Db;

class InstrumentedList extends ModelInstanceIterator {
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

    function add($object) {
        if (!$object || !$object instanceof $this->model)
            throw new Exception('Attempting to add invalid object to list');

        $object->{$this->key} = $this->id;
        $object->save();
        $this->list[] = $object;
    }
    function remove($object) {
        $object->delete();
    }

    function offsetUnset($a) {
        $this->fillTo($a);
        $this->cache[$a]->delete();
    }
    function offsetSet($a, $b) {
        $this->fillTo($a);
        $this->cache[$a]->delete();
        $this->add($b);
    }
}
