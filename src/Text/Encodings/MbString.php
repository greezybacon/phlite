<?php

namespace Phlite\Text\Encodings;

use Phlite\Text\Bytes;
use Phlite\Text\Codec;
use Phlite\Text\CodecInfo;
use Phlite\Text\Unicode;

class MbString extends CodecInfo {

    var $target_charset;

    function __construct($target_charset) {
        $this->target_charset = $target_charset;
    }

    function encode($what, $errors=false) {
        if ($what instanceof Unicode) {
            return new Unicode(
                mb_convert_encoding((string) $what, $what->target_charset, $this->charset),
                $this->target_charset
            );
        }
    }
    
    function decode($what, $errors=false) {
        return new Unicode(
            mb_convert_encoding((string) $what, Unicode::$default_encoding,
                $this->target_charset)
        );
    }
}

Codec::register(function($encoding) {
    static $mb_encodings = false;
    
    if (!extension_loaded('mbstring'))
        return;
        
    if (!$mb_encodings)
        $mb_encodings = array_map('strtolower', mb_list_encodings());
    
    if (in_array(strtolower($encoding), $mb_encodings))
        return new MbString($encoding);
});
