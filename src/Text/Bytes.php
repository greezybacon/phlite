<?php

namespace Phlite\Text;

use Phlite\Text\BaseString;

class Bytes extends BaseString {

    protected $string;

    function __construct(&$string) {
        $this->string = &$string;
    }

    function substr($offset, $length=false) {
        return substr($this->string, $offset, $length);
    }

    function unpack($format) {
        return unpack($this->string, $format);
    }

}
