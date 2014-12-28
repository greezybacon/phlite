<?php

namespace Phlite\Http;

/**
 * Slight extension of the ArrayObject which adds convenience methods for
 * interacting with query strings.
 */
class QueryArray
extends Util\ArrayObject {
    
    $this->frozen = false;
    
    function __construct($query, $frozen=true) {
        if (is_string($query)) {
            $array = array();
            parse_str($query, $array);
            $query = $array;
        } 
        parent::__construct($query);
    }
    
    static function fromQueryString($query) {
        return new static($query, false)
    }
    
    function toQuery($separator='&') {
        return http_build_query($this->storage, '', $separator);
    }
    
    function offsetSet($offset, $value) {
        if ($this->frozen)
            throw new \Exception('QueryDict is frozen');
        return parent::offsetSet($offset, $value);
    }
    function offsetUnset($offset) {
        if ($this->frozen)
            throw new \Exception('QueryDict is frozen');
        return parent::offsetUnset($offset);
    }
}