<?php

namespace Phlite\Text;

class Unicode
extends Bytes {

    protected $encoding;
    
    static $default_encoding = 'utf-8';

    function __construct($text, $encoding=false) {
        parent::__construct($text);
        $this->encoding = $encoding ?: self::$default_encoding;
    }

    function __toString() {
        return (string) $this->string;
    }

    /**
     * Function: encode
     *
     * Convert from internal encoding to the given encoding
     */
    function encode($encoding, $errors=false, $normalize=true) {
        // TODO: Normalize the encoding name
        if (0 === strcasecmp($encoding, $this->encoding))
            // No change was requested. Just return the object unchanged
            return $this;
        return Codec::encode($this, $encoding, $errors);
    }

    /**
     * Function: decode
     *
     * Convert from the declared encoding to the internal encoding
     */
    function decode($encoding, $errors=false) {
        return Codec::decode($this, $encoding, $errors);
    }
    
    function getEncoding() {
        return $this->encoding;
    }
    
    function get($encoding=false) {
        if ($encoding)
            return $this->encode($encoding)->get();
        return parent::get();
    }

    function substr($offset, $length=null) {
        return new static(
            mb_substr($this->string, $offset, $length, $this->encoding),
            $this->encoding
        );
    }

    function splice($offset, $length, $replacement="") {
        if ($replacement && $replacement instanceof static)
            $replacement = $replacement->encode($this->encoding);
        return new static($this->substr(0, $offset)
            . (string) $replacement
            . $this->substr($offset + $length),
            $this->encoding);
    }
    
    function append($what) {
        if ($what instanceof Unicode) {
            $what = $what->get($this->encoding);
        }
        $this->string .= $what;
        unset($this->length);
    }

    function upper() {
        return new static(mb_strtoupper($this->string, $this->encoding),
            $this->encoding);
    }
    
    function lower() {
        return new static(mb_strtolower($this->string, $this->encoding),
            $this->encoding);
    }

    function capitalize() {
        return new static(mb_convert_case($this->string, $this->encoding),
            MB_CASE_TITLE, $this->encoding);
    }

    function length() {
        if (!isset($this->length)) {
            $this->length = mb_strlen($this->string, $this->encoding);
        }
        return $this->length;
    }

    function width() {
        return mb_strwidth($this->string, $this->encoding);
    }

    function wrap($width, $separator="\n", $cut=false) {
    }

    // Regex interface
    function split($marker="", $max=false) {
        // XXX: This only works if this->encoding is utf-8
        return array_map(function($t) { return new static($t, 'utf-8'); },
            preg_split('/'.preg_quote($marker).'/u', $this->string));
    }

    function trim($chars=false) {
    }

    function ltrim($chars=false) {
    }

    function rtrim($chars=false) {
    }
    
    // ArrayAccess interface
    function offsetExists($offset) {
        return $this->length() > $offset;
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

if (!function_exists('mb_convert_encoding')) {
    require_once(dirname(__file__) . '/Compat.php');
    \Compat::shim();
}

function u($what, $encoding=false) {
    return new Unicode($what, $encoding);
}
