<?php

namespace Phlite\Db;

/**
 * Class: Db\Backend
 *
 * Connector between the database Manager and the backend Compiler.
 */
abstract class Backend {

    abstract function __construct(array $info);

    abstract function connect();

    /**
     * Gets a compiler compatible with this database engine that can compile
     * and execute a queryset or DML request.
     */
    abstract function getCompiler($options=false);
    
    abstract function getExecutor(Statement $stmt);
    
    function execute(Statement $stmt) {
        $exec = $this->getExecutor($stmt);
        $exec->execute();
        return $exec;
    }
}