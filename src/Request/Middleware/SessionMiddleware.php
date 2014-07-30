<?php

namespace Phlite\Request\Middleware;

use Phlite\Request\Session;

class SessionMiddleware extends Middleware {

    function processRequest($request) {
        // TODO: Initialize the session backend and restore the session
        $session = new Session();
        $request->session = $session;
    }

}
