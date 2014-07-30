<?php

namespace Phlite\Text;

use \ArrayAccess;
use \Countable;

class Unicode implements ArrayAccess, Countable {

    private $content;
    private $length;
    private $encoding;
    
    static $default_encoding = 'utf-8';

    function __construct($text, $encoding=false) {
        if ($encoding && $encoding != self::$default_encoding)
            $this->content = Codec::decode($text, $encoding);
        else
            $this->content = $text;

        $this->encoding = self::$default_encoding;
    }

    function __toString() {
        return $this->content;
    }

    /**
     * Function: encode
     *
     * Convert from internal encoding to the given encoding
     */
    function encode($encoding, $errors=false) {
        return new Unicode(
            Codec::encode($this, $encoding, $errors));
    }

    /**
     * Function: decode
     *
     * Convert from the declared encoding to the internal encoding
     */
    function decode($encoding, $errors=false) {
        return new Unicode(
            Codec::decode($this, $encoding, $errors));
    }

    function join($array) {
    }

    function substr($offset, $length=null) {
        return mb_substr($this, $offset, $length, $this->encoding);
    }

    function splice($offset, $length, $replacement="") {
        if ($replacement && !$replacement instanceof BaseString)
            $replacement = $replacement->encode($this->encoding);
        return new Unicode($this->substr(0, $offset)
            . (string) $replacement
            . $this->substr($offset + $length),
            $this->encoding);
    }

    function upper() {
        return new Unicode(mb_strtoupper($this, $this->encoding),
            $this->encoding);
    }
    
    function lower() {
        return new Unicode(mb_strtolower($this, $this->encoding),
            $this->encoding);
    }

    function capitalize() {
        return new Unicode(mb_convert_case($this, $this->encoding),
            MB_CASE_TITLE, $this->encoding);
    }

    function length() {
        if (!isset($this->length)) {
            $this->length = mb_strlen($this->content, $this->encoding);
        }
        return $this->length;
    }
    function count() {
        return $this->length();
    }

    function width() {
        return mb_strwidth($this->content, $this->encoding);
    }

    function wrap($width, $separator="\n", $cut=false) {
    }

    // Regex interface
    function split($marker="") {
        // NOTE: This only works if this->encoding is utf-8
        return array_map(function($t) { return new Unicode($t, 'utf-8'); },
            preg_split('/'.preg_quote($marker).'/u', $this->content));
    }

    function trim($chars=false) {
    }

    function ltrim($chars=false) {
    }

    function rtrim($chars=false) {
    }
    
    // ArrayAccess interface
    function offsetExists($offset) {
        return $this->length > $offset;
    }
    function offsetGet($offset) {
        return $this->substr($offset, 1, 1);
    }
    function offsetSet($offset, $value) {
        return $this->splice($offset, 1, $value);
    }
    function offsetUnset($offset) {
        return $this->splice($offset, 1, "");
    }
}

function u($what, $encoding=false) {
    return new Unicode($what, $encoding);
}
