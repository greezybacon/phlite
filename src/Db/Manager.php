<?php

namespace Phlite\Db;

use Phlite\Project;

/**
 * Database manager singleton. For a running project, there is one database
 * manager. The manager is responsible for determining the database backend
 * for all models. It connects with the database routing and transaction
 * coordination systems to provide access to retrieve and update objects
 * across various database backends.
 */
class Manager {
    
    protected $routers = array();
    protected $connections = array();
    protected $transaction;

    static function getManager() {
        static $manager;
        
        if (!isset($manager)) {
            $manager = new static();
        }
        return $manager;
    }
        
    protected function addConnection(array $info, $key) {
        if (!isset($info['BACKEND']))
            throw new \Exception("'BACKEND' must be set in the database options.");
        $backendClass = $info['BACKEND'] . '\Backend';
        if (!class_exists($backendClass))
            throw new \Exception($backendClass
                . ': Specified database backend does not exist');
        $this->connections[$key] = new $backendClass($info);
    }
    
    /**
     * tryAddConnection
     *
     * Attempt to add a new connection, by name, from the current project's
     * configuration settings.
     *
     * Returns:
     * <bool> TRUE upon success.
     */
    protected function tryAddConnection($key) {
        $databases = Project::getCurrent()->getSetting('DATABASES');
        if ($databases && is_array($databases) && isset($databases[$key])) {
            $this->addConnection($databases[$key], $key);
            return true;
        }
    }
    
    /**
     * getConnection
     *
     * Fetch a connection object for a particular model.
     *
     * Returns:
     * (Connection) object which can handle queries for this model. 
     * Optionally, the $key passed to ::addConnection() can be returned and
     * this Manager will lookup the Connection automatically.
     */
    protected function getConnection($model) {
        if ($model instanceof Model\ModelBase)
            $model = get_class($model);
        foreach ($this->routers as $R) {
            if ($C = $R->getConnectionForModel($model)) {
                if (is_string($C)) {
                    if (!isset($this->connections[$C]) && !$this->tryAddConnection($C))
                        throw new \Exception($backend 
                            . ': Backend returned from routers does not exist.');
                    $C = $this->connections[$C];
                }
                return $C;
            }
        }
        if (!isset($this->connections['default']) && !$this->tryAddConnection('default'))
            throw new \Exception("'default' database not specified");
        return $this->connections['default'];
    }
    
    protected function addRouter(Router $router) {
        $this->routers[] = $router;
    }
    
    protected function getCompiler(Model\ModelBase $model) {
        return $this->getConnection($model)->getCompiler();
    }
    
    /**
     * delete
     *
     * Delete a model object from the underlying database. This method will
     * also clear the model cache of the specified model so future lookups
     * would mean database lookups or NULL. 
     *
     * Returns:
     * TRUE if the object was successfully deleted and FALSE otherwise. The
     * callback will be invoked immediately unless there is a transaction in
     * progress, in which case the callback will be invoked when the
     * transaction is committed.
     */
    protected static function delete(Model\ModelBase $mode, $callback, $args=null) {
        if (isset($this->transaction)) {
            return $this->transaction->delete($model, $callback, $args);
        }
        return $this->_delete($model, $callback, $args);
    }

    protected function _delete($model, $callback, $args=null) {
        Model\ModelInstanceManager::uncache($model);
        $connection = $this->getConnection($model);
        $stmt = $connection->getCompiler()->compileDelete($model);
        $ex = $connection->getExecutor($stmt);

        try {
            if (($success = $ex->affected_rows()) == 1) {
                $args = $args ?: array();
                array_unshift($args, $ex);
                $callback($args);
            }
        }
        catch (Exception\OrmError $e) {
            return false;
        }
        return $success;
    }
    
    /**
     * save
     *
     * Commit model changes to the database. This method will compile an
     * insert or an update as necessary. If there is a transaction in
     * progress, the model is not saved immediately. Instead, it is added to
     * the transaction and will be committed later.
     *
     * Returns:
     * <SqlExecutor> — an instance of SqlExecutor which can perform the
     * actual save of the model (via ::execute()). Thereafter, query
     * ::insert_id() for an auto id value and ::affected_rows() for the 
     * count of affected rows by the update (should be 1).
     */
    protected static function save(Model\ModelBase $model, $callback, $args=null) {
        if (isset($this->transaction)) {
            return $this->transaction->add($model, $callback, $args);
        }
        return $this->_save($model, $callback, $args);
    }

    static function _save(Model\ModelBase $model, $callback, $args=null) {
        $connection = $this->getConnection($model);
        $compiler = $connection->getCompiler();
        $wasnew = $model->__new__
        if ($wasnew)
            $stmt = $compiler->compileInsert($model);
        else
            $stmt = $compiler->compileUpdate($model);
        
        $ex = $connection->getExecutor($stmt);
        try {
            $ex->execute();
            $rows = $ex->affected_rows();
            if ($rows != 1) {
                // This doesn't really signify an error. It just means that
                // the database believes that the row did not change. For
                // inserts though, it's a deal breaker
                if ($wasnew) {
                    return false;
                }
            }
            $args = $args ?: array();
            array_unshift($args, $ex);
            $callback($args);
        }
        catch (Exception\OrmError $e) {
            return false;
        }
        return true;
    }

    /**
     * Start all model updates in a transaction. All future calls to
     * ::save() and ::delete() will be placed in the transaction and
     * commited with the transaction.
     */
    protected function beginTransaction($mode=null) {
        if (isset($this->transaction))
            throw new Exception\OrmError('Transaction already started. Use `commit` or `rollback` to complete the current transaction before staring a new one');

        $this->transaction = new TransactionCoordinator($this);
    }

    protected function flush() {
        if ($this->transaction)
            return $this->transaction->flush();
    }

    /**
     * Commit the current transaction. Transactions must be started with
     * ::beginTransaction(). Transactions are automatically coordinated
     * among several databases where supported.
     */
    protected function commit() {
        if (!isset($this->transaction))
            throw new Exception\OrmError('Transaction not started');

        $rv = $this->transaction->commit();
        unset($this->transaction);
        return $rv;
    }

    protected function rollback() {
        if (!isset($this->transaction))
            throw new Exception\OrmError('Transaction not started');

        $rv = $this->transaction->rollback();
        unset($this->transaction);
        return $rv;
    }
    
    // Allow "static" access to instance methods of the Manager singleton. All
    // static instance methods are hidden to allow routing through this
    // singleton handler
    static function __callStatic($name, $args) {
        $manager = static::getManager();
        return call_user_func_array(array($manager, $name), $args);
    }
}
