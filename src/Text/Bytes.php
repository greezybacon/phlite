<?php

namespace Phlite\Text;

class Bytes extends BaseString
implements String {

    // Wrappers for built in functions
    function explode($sep, $max=false) {
        return new static(explode($sep, $this->get(), $max));
    }
    function indexOf($needle) {
        return strpos($this->get(), $needle);
    }
    function lower() {
        return new static(strtolower($this->get()));
    }
    function ltrim($chars=false) {
        return new static(ltrim($this->get(), $chars));
    }
    function substr($offset, $length=false) {
        return $length
            ? new static(substr($this->get(), $offset, $length))
            : new static(substr($this->get(), $offset));
    }
    function trim($chars=false) {
        return new static(trim($this->get()));
    }
    function rtrim($chars=false) {
        return new static(rtrim($this->get(), $chars));
    }
    function unpack($format) {
        return unpack($format, $this->get());
    }
    function upper() {
        return new static(strtoupper($this->get()));
    }
    
    // Extras
    function capitalize() {
    }
    function substrCount($substr) {
        return preg_match_all('`(?:'.preg_quote($substr).')`');
    }
    function splice($offset, $length, $repl='') {
        // FIXME: Implement ::splice
    }
    function endsWith($text) {
        $text = static::from($text);
        $length = $text->length();
        if ($length === 0)
            return true;
        return $this->substr(-$length)->equals($text);
    }
    function startsWith($text) {
        return $this->indexOf($text) === 0;
    }
    function width() {
        return $this->length();
    }
    function wrap($width, $delimiter="\n", $cut=false) {
        return wordwrap($this->get(), $width, $delimiter, $cut);
    }
    
    // Comparison
    function equals($what) {
        return $this->get() == $what;
    }
    function cmp($what) {
        return strcmp($this->get(), $what);
    }
    
    // Regex interface
    function matches($pattern) {
        return preg_match($pattern, $this->get());
    }
    function replace($pattern, $repl=false, $max=false) {
        if (is_callable($callback))
            $repl = preg_replace_callback($pattern, $repl, $this->get(), $max ?: -1);
        else
            $repl = preg_replace($pattern, $repl, $this->get(), $max ?: -1);
        return new static($repl);
    }
    function search($pattern) {
        $matches = array();
        if (preg_match_all($pattern, $this->get(), $matches))
            return $matches;
        return false;
    }
    function split($pattern, $max=false) {
        return preg_split($pattern, $this->get(), $max ?: -1);
    }
    
    // Hashlib
    function hash($algo) {
        return new static(hash($algo, $this->get(), true));
    }
    
    /**
     * Function: decode
     *
     * Convert from the declared encoding to the internal encoding
     */
    function decode($encoding, $errors=false) {
        return Codec::decode($this, $encoding, $errors);
    }
    // This really only makes sense with encodings like 'hex'
    function encode($encoding, $errors=false) {
        return Codec::encode($this, $encoding, $errors);
    }

    static function from($what) {
        if (is_array($what))
            return array_map(array(get_called_class(), 'from'), $what);
        if (!$what instanceof String) {
            $what = new static($what);
        }
        return $what;
    }
}
