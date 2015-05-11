<?php

namespace Phlite\Text\Encodings;

use Phlite\Text;

class Html extends Text\CodecInfo {

    function encode($obj, $errors=false) {
        $flags = ENT_COMPAT | ENT_HTML401;
        return htmlspecialchars((string) $obj, $flags,
            $obj instanceof Text\Unicode ? $obj->getEncoding() : 'utf-8');
    }

    function decode($obj, $errors=false) {
        $flags = ENT_COMPAT | ENT_HTML401;
        return htmlspecialchars_decode((string) $obj, $flags, $errors == 'strict',
            $obj->encoding);
    }
}

Text\Codec::register(function($encoding) {
    if ($encoding == 'html')
        return new Html();
});
