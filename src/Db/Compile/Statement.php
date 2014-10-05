<?php

namespace Phlite\Db;

class Statement {
    
    var $sql;
    var $params;
    var $map;
    
    function __construct($sql, $params=false, $map=false) {
        $this->sql = $sql;
        $this->params = $params;
        $this->map = $map;
    }
    
    function __toString() {
        return $this->sql;
    }
}