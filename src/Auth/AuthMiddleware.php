<?php

namespace Phlite\Auth;

use Phlite\Auth\UnauthorizedException;

class AuthMiddleware {
    function processRequest($request) {
        if (!$request->session)
            return;

        $request->user = AuthenticationBackend::getUser($request->session);
    }

    function processView($request, $view) {
        if ($view instanceof AuthenticationRequired) {
            $view->processPolicy($request);
        }
        $handler = $view->getHandler();
        if ($handler instanceof AuthenticationRequired) {
            if (!($user = $request->user)) {
                $view->setHandler($handler->getAuthView());
                return true;
            }
            $role = $handler->getRequiredRole();
            if (!$user->hasRole($role))
                throw new UnauthorizedException();
        }
    }
}
