<?php

namespace Phlite\Db\Fields;

abstract class BaseField {
    
    function __construct($options, $name) {
        
    }
    
    /**
     * Convert a value from this field to a database value
     */
    function to_database($value, $compiler=false) {
        return $value;
    }
    
    /**
     * Convert a value from the database to a PHP value.
     */
    function to_php($value, $connection) {
        return $value;
    }
    
    /**
     * Cooperate in a CREATE TABLE statement for SqlCompilers
     */
    function create($compiler) {
        
    }
}