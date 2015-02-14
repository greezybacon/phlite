<?php

namespace Phlite\Http;

use Phlite\Util;

/**
 * HeaderArray
 *
 * Simple extension to the array object, which is used to wrap the $_SERVER
 * variables. The class also provides access to all headers by trying harder
 * to find the header if requested, by consulting the environment as well as
 * providing access to all headers exposed through the getallheaders() PHP
 * function.
 */
class HeaderArray
extends Util\ArrayObject {
     
    function __construct() {
        parent::__construct($_SERVER);
    }

    function offsetGet($header) {
        if (isset($this->storage[$header]))
            return $this->storage[$header];
        elseif (isset($_ENV[$header]))
            return $_ENV[$header];
    }
}
