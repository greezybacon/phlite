<?php

namespace Phlite\Security;

class BasePolicy
implements Policy {
    /**
     * login_required
     *
     * Flag to indicate if a login is required to perform this operation.
     * If not, then the $permission and $user_class options are ignored
     */
    static $login_required = true;
      
    /**
     * require_permission
     *
     * The name of a permission that must be possessed in order to perform
     * this operation. If this is defined by an Operation subclass, then
     * when the operation is requested, a user must be logged in and must
     * also possess this permission in order to perform the operation.
     */
    static $require_permission = false;
    
    /**
     * user_class
     *
     * Type of user (by some base class name) of a class of user which is
     * required to perform the operation. For instance, if your system has
     * the concept of "Adminsitrator" users, an administrative subclass of
     * users can be handled by the authentication system. The class of the
     * user can be checked by this Operation automatically.
     */
    static $user_class = '\Phlite\Auth\Model\User';
    
    /**
     * redirect
     *
     * If this operation cannot be performed due to insufficient
     * privileges, redirect the user to this view.
     */
    static $redirect = false;
    
    function checkUserPermission($request, $operation=null) {
        if (static::$login_required && !$request->user) {
            static::notAllowed($operation);
            throw new Exception\LoginRequired();
        }
        elseif ($requst->user && static::$user_class
             && !$request->user instanceof static::$user_class
        ) {
            static::notAllowed($operation);
            throw new Exception\AccessDenied();
        }
        elseif ($request->user && static::$require
             && !$request->user->hasPerm(static::$require)
        ) {
            static::notAllowed($operation);
            throw new Exception\AccessDenied();
        }
    }
    
    function notAllowed($operation=null) {
        if (static::$redirect) {
            $dispatcher = $request->getHandler()->getDispatcher();
            $url = $dispatcher->reverse(static::$redirect, [], 'GET');
            throw new HttpRedirect($url);
        }
    }
}