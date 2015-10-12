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
    
    abstract function getExecutor(Compile\Statement $stmt);
    
    function execute(Compile\Statement $stmt) {
        $exec = $this->getExecutor($stmt);
        $exec->execute();
        return $exec;
    }

    // Transaction interface
    abstract function rollback();
    abstract function commit();
    abstract function beginTransaction();

    // Backend must implement DistributedTransaction if able to participate
    function startDistributed() {
        if (!$this instanceof DistributedTransaction)
            throw new Exception\OrmError('Database backend does not support distributed transactions. You cannot combine objects from multiple backends in a transaction with this backend');
    }
}
