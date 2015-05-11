<?php

namespace Phlite\Text\Encodings;

use Phlite\Text\Bytes;
use Phlite\Text\Codec;
use Phlite\Text\CodecInfo;

class Base64 extends CodecInfo {
    static $name = 'base64';

    function encode($obj, $errors=false) {
        return new Bytes(base64_encode($obj));
    }

    function decode($obj, $errors=false) {
        return new Bytes(base64_decode($obj, $errors == 'strict'));
    }
}

class Hex extends CodecInfo {
    static $name = 'hex';

    function encode($obj, $errors=false) {
        return new Bytes(bin2hex($obj));
    }

    function decode($obj, $errors=false) {
        return new Bytes(hex2bin($obj, $errors == 'strict'));
    }
}

Codec::register(function($encoding) {
    if ($encoding == 'base64')
        return new Base64();
    if ($encoding == 'hex')
        return new Hex();
});
