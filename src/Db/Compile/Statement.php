<?php

namespace Phlite\Db;

/**
 * Class: Statement
 *
 * Statement represents the data necessary to execute a query. It contains
 * the SQL text, parameters, and output map. The output map correlates the
 * returned fields with the models to which they pertain.
 */
class Statement {
    
    var $sql;
    var $params;
    var $map;
    
    function __construct($sql, $params=false, $map=false) {
        $this->sql = $sql;
        $this->params = $params;
        $this->map = $map;
    }
    
    function getMap() {
        return $this->map;
    }
    
    function __toString() {
        return $this->sql;
    }
}