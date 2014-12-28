<?php

namespace Phlite\Db;

abstract class Router {

    /**
     * Return a Connection instance for the model in question. This is used
     * to connect models to various database connections and backends based
     * on criteria defined in Router instances. Use Manager::addRouter() to
     * register a new router.
     *
     * If a router does not handle a particular model, it need not return a
     * value
     *
     * Returns:
     * (string) name of the database connection information registered in 
     * the project settings file.
     */
    abstract function getConnectionForModel(ModelBase $model);
    
    // TODO: Consider allows switch of server connection for updates.
}