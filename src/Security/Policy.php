<?php

namespace Phlite\Security;

interface Policy {

    /**
     * checkUserPermission
     *
     * Verifies if the current user is logged in and if the user posesses
     * the permission required to perform this operation. If not, the
     * ::notAllowed() method is invoked which allows the Operation to
     * redirect or raise an appropriate HTTP response
     */
    function checkUserPermission($request, $view=null);
    
    /**
     * notAllowed
     *
     * The current user is definitely NOT allowed to access this view.
     * This handler method can be used to define what happens next. Normally,
     * the user is either redirected to the view cited in the ::$redirect
     * variable. Otherwise an HTTP exception is thrown and displayed to the
     * user.
     */
    function notAllowed($view=null);
}