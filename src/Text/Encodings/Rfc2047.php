<?php

namespace Phlite\Text\Encodings;

use Phlite\Text\Codec;

class Rfc2047 extends Codec {
    function decode($what, $errors) {
        if(function_exists('imap_mime_header_decode')
                && ($parts = imap_mime_header_decode($text))) {
            $str ='';
            foreach ($parts as $part)
                $str.= Format::encode($part->text, $part->charset, $encoding);

            $text = $str;
        } elseif($text[0] == '=' && function_exists('iconv_mime_decode')) {
            $text = iconv_mime_decode($text, 0, $encoding);
        // TODO: Use a pure-PHP version to perform the decoding
        } elseif(!strcasecmp($encoding, 'utf-8')
                && function_exists('imap_utf8')) {
            $text = imap_utf8($text);
        }

        return $text;
    }
}

Codec::register(function($encoding) {
    if (strcasecmp('rfc2047, '$encoding) === 0) {
        return new Rfc2047($encoding);
    }
});