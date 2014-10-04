<?php

namespace Phlite\Db;

class DbEngine {

    static $compiler = 'Backend\MySql\Compiler';

    function __construct($info) {
    }

    function connect() {
    }

    // Gets a compiler compatible with this database engine that can compile
    // and execute a queryset or DML request.
    static function getCompiler() {
        $class = static::$compiler;
        return new $class();
    }

    static function delete(ModelBase $model) {
        ModelInstanceManager::uncache($model);
        return static::getCompiler()->compileDelete($model);
    }

    static function save(ModelBase $model) {
        $compiler = static::getCompiler();
        if ($model->__new__)
            return $compiler->compileInsert($model);
        else
            return $compiler->compileUpdate($model);
    }
}