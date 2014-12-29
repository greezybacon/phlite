<?php

namespace Phlite\Text;

use \DomainException;

class Codec {
    
    private static $registry = array();

    static function encode($what, $encoding='ascii', $errors='strict') {
        return static::lookup($encoding)->encode($what, $errors);
    }

    static function decode($what, $encoding='ascii', $errors='scrict') {
        return static::lookup($encoding)->decode($what, $errors);
    }

    static function register($search_func) {
        if (!is_callable($search_func))
            throw new InvalidArgumentException(
                'Only callables can be registered');
        static::$registry[] = $search_func;
    }

    static function lookup($encoding) {
        $encoding = strtolower($encoding);
        foreach (static::$registry as $f) {
            if ($codec = $f($encoding))
                return $codec;
        }
        throw new DomainException($encoding.': Unable to find codec');
    }
}

// Load standard encodings
require_once 'Encodings/Base64.php';
require_once 'Encodings/MbString.php';