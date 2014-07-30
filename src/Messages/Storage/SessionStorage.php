<?php

namespace Phlite\Messages\Storage;

use Phlite\Messages\Storage\BaseStorage;

class SessionStorage extends BaseStorage {

    function __construct($request) {
        parent::__construct();
        $this->list = &$request->session[':msgs'];
    }

    function get() {
        return $this->list;
    }

    function store($messages, $response) {
        $this->list = $messages;
        return array();
    }
}
