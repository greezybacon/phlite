<?php

namespace Phlite;

use Phlite\Util\Dict;

class Session extends Dict {

    var $root;

    function __construct($root=':s') {
        $this->root = $root;
        parent::__construct();
        $this->storage = &$_SESSION[$root];
    }
}
