<?php

namespace Phlite\Text\Encodings;

use Phlite\Text\Bytes;
use Phlite\Text\Codec;
use Phlite\Text\CodecInfo;
use Phlite\Text\Unicode;

class Rfc2047 extends CodecInfo {
    function decode($what, $errors=false) {
        if (function_exists('imap_mime_header_decode')
                && ($parts = imap_mime_header_decode($text))) {
            $str ='';
            foreach ($parts as $part)
                $str .= Format::encode($part->text, $part->charset, $encoding);
            return $str;
        }
        elseif ($text[0] == '=' && function_exists('iconv_mime_decode')) {
            return iconv_mime_decode($text, 0, $encoding);
        // TODO: Use a pure-PHP version to perform the decoding
        }
        elseif (!strcasecmp($encoding, 'utf-8')
                && function_exists('imap_utf8')) {
            return imap_utf8($text);
        }

        return $text;
    }
    
    function encode($what, $errors=false) {
    }
    
}

class QuotedPrintable extends CodecInfo {
    function decode($what, $errors=false) {
        return new Bytes(quoted_printable_decode($what));
    }
    
    function encode($what, $errors=false) {
        return new Bytes(quoted_printable_encode($what));
    }
}

Codec::register(function($encoding) {
    if (strcasecmp('rfc2047', $encoding) === 0) {
        return new Rfc2047($encoding);
    }
    if (strcasecmp('qp', $encoding) === 0) {
        return new QuotedPrintable();
    }
});