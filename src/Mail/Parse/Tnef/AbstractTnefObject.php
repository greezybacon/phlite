<?php

namespace Phlite\Mail\Parse\Tnef;

abstract class AbstractTnefObject {
    function _setProperties($propReader) {
        foreach ($propReader as $prop=>$info) {
            if ($tag = TnefAttribute::getName($prop))
                $this->{$tag} = $info['value'];
            elseif ($prop == 0x9999)
                // Extended, "named" attribute
                $this->{$info['named_id']} = $info['value'];
        }
    }

    function _set($prop, $value) {
        $this->{$prop} = $value;
    }
}
