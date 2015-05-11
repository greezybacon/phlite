<?php

namespace Phlite\Text;

use \DomainException;

class Codec {
    
    private static $registry = array();

    static function encode($what, $encoding='ascii', $errors='strict') {
        $rv = static::lookup($encoding)->encode($what, $errors);
        if (!$rv instanceof String)
            $rv = new Bytes($rv);
        return $rv;
    }

    static function decode($what, $encoding='ascii', $errors='scrict') {
        $rv = static::lookup($encoding)->decode($what, $errors);
        if (!$rv instanceof String)
            $rv = new Bytes($rv);
        return $rv;
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
    
    /**
     * Convenience method to load a module from the Encodings/ folder
     */
    static function load($module) {
        include_once "Encodings/{$module}.php";
    }
}

// Load standard encodings
Codec::load('basic');
Codec::load('mbstring');