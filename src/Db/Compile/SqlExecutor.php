<?php

namespace Phlite\Db\Compile;

use Phlite\Db\Backend;

interface SqlExecutor {
    
    function __construct(Statment $stmt, Backend $bk);
    
    // Execute the statement — necessary for DML statements
    function execute();
    // Release resouces from the statement and records
    function close();
    
    function fetchRow();
    function fetchArray();
    
    /**
     * insert_id
     *
     * Fetch auto-id of previous insert statement
     */
    function insert_id();
    
    /**
     * affected_rows
     *
     * Retrieve the number of affected rows from the previous DML statement
     */
    function affected_rows();
}