<?php

namespace Phlite\Core\Session;

use Phlite\Core\Session;
use Phlite\Request\Middleware;

class SessionMiddleware extends Middleware {

    function processRequest($request) {
        $settings = $request->getSettings();
        $bk = $settings->get('SESSION_BACKEND');
        
        $session = new $bk($request->COOKIES->get(
            $settings->get('SESSION_COOKIE_NAME', null))
        );
                        
        // Verify a few items
        //if ($session->isValid())
            $request->session = $session;
    }
    
    function processResponse($request, $response) {
        $settings = $request->getSettings();
        
        if ($request->session->isModified()) {
            $request->session->save();
            // XXX: $response->setCookie()
            setcookie(
                $settings->get('SESSION_COOKIE_NAME'),
                $request->session->getSessionKey(),
                $request->session->getExpiryTime(),
                $request->getRootPath(),
                $request->getCookieDomain(),
                $request->isHttps(),
                $settings->get('SESSION_COOKIE_HTTPONLY', null)
            );
        }
    }

}
