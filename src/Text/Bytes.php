<?php

namespace Phlite\Text;

class Bytes extends BaseString {

    function substr($offset, $length=false) {
        return substr($this->string, $offset, $length);
    }

    function unpack($format) {
        return unpack($this->string, $format);
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

}
