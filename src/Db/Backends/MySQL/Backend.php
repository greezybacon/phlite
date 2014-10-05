<?php

namespace Phlite\Db\Backends\MySQL;

class Backend {
    
    static $compiler = __NAMESPACE__ . '\Compiler';
    static $executor = __NAMESPACE__ . '\Executor';
    
    var $info;
    
    function __construct(array $info) {
        $this->info = $info;
    }
    
    function getCompiler($options) {
       $class = static::$compiler;
       return new $class($this, $options);
    }
    
    function getExecutor(Statement $stmt) {
        $class = static::$executor;
        return new $class($stmt);
    }
}