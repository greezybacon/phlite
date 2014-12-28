<?php

namespace Phlite\Db;

class Manager {
    
    protected $routers = array();
    protected $connections = array();
    
    static function getManager() {
        static $manager;
        
        if (!isset($manager)) {
            $manager = new Manager();
        }
        return $manager;
    }
        
    function addConnection(array $info, $key) {
        if (!isset($info['BACKEND']))
            throw new \Exception("'BACKEND' must be set in the database options.");
        $backendClass = $info['BACKEND'] . '\Backend';
        if (!class_exists($backendClass))
            throw new \Exception($backendClass
                . ': Specified database backend does not exist');
        $connections[$key] = new $backendClass();
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
    function getConnection($model) {
        if ($model instanceof Model\ModelBase)
            $model = get_class($model);
        foreach ($this->routers as $R) {
            if ($C = $R->getConnectionForModel($model)) {
                if (is_string($C)) {
                    if (!isset($this->connections[$C]))
                        throw new \Exception($backend 
                            . ': Backend returned from routers does not exist.');
                     $C = $this->connections[$C]  
                }
                return $C;
            }
        }
        if (!isset($this->connections['default']))
            throw new \Exception("'default' database not specified");
        return $this->connections['default'];
    }
    
    function addRouter(Router $router) {
        $this->routers[] = $router;
    }
    
    function getCompiler(Model\ModelBase $model) {
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
     * SqlExecutor — an instance of SqlExecutor which can perform the
     * actual execution (via ::execute())
     */
    static function delete(Model\ModelBase $model) {
        Model\ModelInstanceManager::uncache($model);
        $connection = static::getManager()->getConnection($model);
        $stmt = $conection->getCompiler()->compileDelete($model);
        return $connection->getExecutor($stmt);
    }
    
    /**
     * save
     *
     * Commit model changes to the database. This method will compile an
     * insert or an update as necessary.
     *
     * Returns:
     * SqlExecutor — an instance of SqlExecutor which can perform the
     * actual save of the model (via ::execute()). Thereafter, query
     * ::insert_id() for an auto id value and ::affected_rows() for the 
     * count of affected rows by the update (should be 1).
     */
    static function save(Model\ModelBase $model) {
        $connection = static::getManager()->getConnection($model);
        $compiler = $connection->getCompiler();
        if ($model->__new__)
            $stmt = $compiler->compileInsert($model);
        else
            $stmt = $compiler->compileUpdate($model);
        
        return $connection->getExecutor($stmt);
    }
    
    // Allow "static" access to instance methods of the Manager singleton
    static function __callStatic($name, $args) {
        $manager = static::getManager();
        return call_user_func_array(array($manager, $name), $args);
    }
}