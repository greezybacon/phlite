<?php

namespace Phlite\Text\Encodings;

use Phlite\Text\Codec;
use Phlite\Text\CodecInfo;

class Base64 extends CodecInfo {
    var $name = 'base64';

    function encode($obj, $errors=false) {
        return base64_encode($obj);
    }

    function decode($obj, $errors=false) {
        return base64_decode($obj, $errors == 'strict');
    }
}

Codec::register(function($encoding) {
    if ($encoding == 'base64')
        return new Base64();
});
