<?php

namespace Phlite\Security\Features\Csrf;

use Phlite\Security;

class CsrfToken
implements \Serializable {
    
    function __construct() {
        $this->token = Security\Random::getRandomText(32);
    }
    
    function check($request) {
        // Find the CSRF-Token in the current request. It can be in the 
        // POST parameters as well as an X-CSRF-Token header
        if ($request->POST['CSRF-Token']) {
            $token = $request->POST['CSRF-Token'];
        }
        elseif ($request->META['HTTP_X_CSRF_TOKEN']) {
            $token = $request->META['HTTP_X_CSRF_TOKEN'];
        }
        
        // Require login and current session
        if ($request->user && $request->session) {
            if ($request->session[':CSRF'] != $token)
                throw new \Exception('Invalid or missing CSRF cookie');
        }
    }
    
    function serialize() {
        return $this->token;
    }
    function unserialize($what) {
        $this->token = $what;
    }
}
