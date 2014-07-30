<?php

namespace Phlite\Db;

abstract class DbEngine {

    function __construct($info) {
    }

    private function __cache($model) {
        // TODO: Back this into a shared cache such as APC ?
        $this->_cache[$this->getKey($model)] = $model;
    }

    function getKey($model, $pk=false) {
        $key = $model::$meta['table'];
        foreach ($model::$pk as $f) {
            $key .= '.'.$model->get($f);
        }
        return $key;
    }

    abstract function getConnection();

    function delete($model) {
        $ex = $this->getCompiler()->compileDelete($model);
        $ex->execute($this);
        return $ex;
    }

    function save($model) {
        if ($model->__new__)
            $ex = $this->getCompiler()->compileInsert($model);
        elseif (count($model->dirty) !== 0)
            $ex = $this->getCompiler()->compileUpdate($model);
        else
            // Nothing to do
            return null;

        $ex->execute($this);
        $this->__cache($model);
        return $ex;
    }

    function get($model, $pk) {
        $key = $this->getKey($model, $pk);
        if (isset($this->_cache[$key]))
            return $this->_cache[$key];
        else {
            // TODO: Build and execute QuerySet, cache the result and return
            // the fetched model
        }
    }

    // Gets a compiler compatible with this database engine that can compile
    // and execute a queryset or DML request.
    /* abstract */ function getCompiler() {}
}

