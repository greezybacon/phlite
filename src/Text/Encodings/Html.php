<?php

namespace Phlite\Text\Encodings;

use Phlite\Text\Codec;
use Phlite\Text\CodecInfo;

class Html extends CodecInfo {

    function encode($obj, $errors=false) {
        return htmlentities((string) $obj);
    }

    function decode($obj, $errors=false) {
        return html_entity_decode((string) $obj, $errors == 'strict',
            $obj->encoding);
    }
}

Codec::register(function($encoding) {
    if ($encoding == 'html')
        return new Html();
});
