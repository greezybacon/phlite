<?php

namespace Phlite\Security\Features\Csrf;

use Phlite\Request;
use Phlite\Security;

/**
 * CSRF
 *
 * Cross-Site Request Forgery protection, which requires that operations
 * requested by the logged-in user were actually requested by that user
 * — not a javascript code running in a neighboring tab.
 */
class Middleware
extends Request\Middleware {
    
    function processView($request, $view) {
        // Automatically add the :CSRF token to the session
        if (isset($request->session) && isset($request->user)) {
            if (!isset($request->session[':CSRF'])) {
                $this->session[':CSRF'] = new CsrfToken();
            }
            if ($view instanceof CsrfProtected) {
                $this->session[':CSRF']->check($request);
            }
        }
    }
}