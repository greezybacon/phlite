<?php

namespace Phlite\Messages\Storage;

use Phlite\Messages\Storage\BaseStorage;
use Phlite\Util;

class SessionStorage extends BaseStorage {

    function __construct($request) {
        parent::__construct($request);
        $this->list = $request->session->setDefault(':msgs', new Util\ListObject());
    }

    function get() {
        return $this->list;
    }

    function store($messages, $response) {
        $this->list = $messages;
        return array();
    }
}
