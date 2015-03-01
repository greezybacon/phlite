<?php

namespace Phlite\Db\Compile;

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
    
    function hasParameters() {
        return count($this->params);
    }
    function getParameters() {
        return $this->params;
    }
    
    function toString($escape_cb=false) {
        if (!$escape_cb)
            $escape_cb = function($i) { return $i; };
        
        $self = $this;
        $x = 0;
        return preg_replace_callback('/\?/', function($m) use ($self, &$x, $escape_cb) {
            $p = $self->params[$x++];
            // FIXME:
            return $escape_cb($p);
        }, $this->sql);
    }
    function __toString() {
        return $this->toString();
    }
}