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
                mb_convert_encoding((string) $what, $this->target_charset,
                $what->getEncoding()), $this->target_charset
            );
        }
    }
    
    function decode($what, $errors=false) {
        if ($what instanceof Bytes)
            return new Unicode($what, $this->target_charset);

        // FIXME: Transcode from unknown source charset?
        return new Unicode(
            mb_convert_encoding((string) $what, Unicode::$default_encoding,
                $this->target_charset)
        );
    }
    
    static function normalize($charset) {
    }
}

if (extension_loaded('mbstring')) {
    Codec::register(function($encoding) {
        static $mb_encodings = false;
            
        if (!$mb_encodings)
            $mb_encodings = array_map('strtolower', mb_list_encodings());

        // Normalize received encoding
    
        if (in_array(strtolower($encoding), $mb_encodings))
            return new MbString($encoding);
    });
}