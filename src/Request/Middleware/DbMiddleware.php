<?php

namespace Phlite\Request\Middleware;

use Phlite\Request\Middleware;

class DbMiddleware extends Middleware {

    function processRequest($request) {
        $this->db = $request->system->get
    }

    function processException($request, $exception) {
        // TODO: Rollback transaction in progress
    }
}
