<?php

namespace Phlite\Auth;

/**
 * Simple authentication backend which will lock the login form after a
 * configurable number of attempts
 */
abstract class AuthStrikeBackend extends AuthenticationBackend {

    function authenticate($username, $password=null) {
        return static::authTimeout();
    }

    function signOn() {
        return static::authTimeout();
    }

    static function signOut($user) {
        return false;
    }


    function login($user, $bk) {
        return false;
    }

    static function getUser() {
        return null;
    }

    function supportsAuthentication() {
        return false;
    }

    function getAllowedBackends($userid) {
        return array();
    }

    function getAuthKey($user) {
        return null;
    }

    //Provides audit facility for logins attempts
    function audit($result, $credentials) {

        //Count failed login attempts as a strike.
        if ($result instanceof AccessDenied)
            return static::authStrike($credentials);

    }

    abstract function authStrike($credentials);
    abstract function authTimeout();
}
