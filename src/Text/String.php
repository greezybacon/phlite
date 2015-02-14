<?php

namespace Phlite\Text;

class String extends BaseString {
    
    function startsWith($what) {
        return strpos($this->string, $what) === 0;
    }
    
    function endsWith($what) {
        $length = strlen($what);
        if ($length === 0)
            return true;
        
        return substr($this->string, -$length) == $what;
    }
    
    function slice($start, $length=false) {
        return $length
            ? new static(substr($this->string, $start, $length))
            : new static(substr($this->string, $start));
    }
}