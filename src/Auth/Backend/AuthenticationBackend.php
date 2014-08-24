<?php

namespace Phlite\Auth;

/**
 * Authentication backend
 *
 * Authentication provides the basis of abstracting the link between the
 * login page with a username and password and the staff member,
 * administrator, or client using the system.
 *
 * The system works by allowing the AUTH_BACKENDS setting from
 * ost-config.php to determine the list of authentication backends or
 * providers and also specify the order they should be evaluated in.
 *
 * The authentication backend should define a authenticate() method which
 * receives a username and optional password. If the authentication
 * succeeds, an instance deriving from <User> should be returned.
 */
abstract class AuthenticationBackend {
    static protected $registry = array();
    static $name;
    static $id;


    /* static */
    static function register($class) {
        if (is_string($class) && class_exists($class))
            $class = new $class();

        if (!is_object($class)
                || !($class instanceof AuthenticationBackend))
            return false;

        return static::_register($class);
    }

    static function _register($class) {
        // XXX: Raise error if $class::id is already in the registry
        static::$registry[$class::$id] = $class;
    }

    static function allRegistered() {
        return static::$registry;
    }

    static function getBackend($id) {

        if ($id
                && ($backends = static::allRegistered())
                && isset($backends[$id]))
            return $backends[$id];
    }

    /*
     * Allow the backend to do login audit depending on the result
     * This is mainly used to track failed login attempts
     */
    static function authAudit($result, $credentials=null) {

        if (!$result) return;

        foreach (static::allRegistered() as $bk)
            $bk->audit($result, $credentials);
    }

    static function process($username, $password=null, &$errors) {

        if (!$username)
            return false;

        $backends =  static::getAllowedBackends($username);
        foreach (static::allRegistered() as $bk) {
            if ($backends //Allowed backends
                    && $bk->supportsAuthentication()
                    && !in_array($bk::$id, $backends))
                // User cannot be authenticated against this backend
                continue;

            // All backends are queried here, even if they don't support
            // authentication so that extensions like lockouts and audits
            // can be supported.
            $result = $bk->authenticate($username, $password);
            if ($result instanceof AuthenticatedUser
                    && ($bk->login($result, $bk)))
                return $result;
            elseif ($result instanceof AccessDenied) {
                break;
            }
        }

        if (!$result)
            $result = new  AccessDenied('Access denied');

        if ($result && $result instanceof AccessDenied)
            $errors['err'] = $result->reason;

        $info = array('username' => $username, 'password' => $password);
        Signal::send('auth.login.failed', null, $info);
        self::authAudit($result, $info);
    }

    /*
     *  Attempt to process non-interactive sign-on e.g  HTTP-Passthrough
     *
     * $forcedAuth - indicate if authentication is required.
     *
     */
    function processSignOn(&$errors, $forcedAuth=true) {

        foreach (static::allRegistered() as $bk) {
            // All backends are queried here, even if they don't support
            // authentication so that extensions like lockouts and audits
            // can be supported.
            $result = $bk->signOn();
            if ($result instanceof AuthenticatedUser) {
                //Perform further Object specific checks and the actual login
                if (!$bk->login($result, $bk))
                    continue;

                return $result;
            }
            elseif ($result instanceof AccessDenied) {
                break;
            }
        }

        if (!$result && $forcedAuth)
            $result = new  AccessDenied('Unknown user');

        if ($result && $result instanceof AccessDenied)
            $errors['err'] = $result->reason;

        self::authAudit($result);
    }

    static function searchUsers($query) {
        $users = array();
        foreach (static::allRegistered() as $bk) {
            if ($bk instanceof AuthDirectorySearch) {
                $users += $bk->search($query);
            }
        }
        return $users;
    }

    /**
     * Fetches the friendly name of the backend
     */
    function getName() {
        return static::$name;
    }

    /**
     * Indicates if the backed supports authentication. Useful if the
     * backend is used for logging or lockout only
     */
    function supportsAuthentication() {
        return true;
    }

    /**
     * Indicates if the backend supports changing a user's password. This
     * would be done in two fashions. Either the currently-logged in user
     * want to change its own password or a user requests to have their
     * password reset. This requires an administrative privilege which this
     * backend might not possess, so it's defined in supportsPasswordReset()
     */
    function supportsPasswordChange() {
        return false;
    }

    function supportsPasswordReset() {
        return false;
    }

    function signOn() {
        return null;
    }

    protected function validate($auth) {
        return null;
    }

    protected function audit($result, $credentials) {
        return null;
    }

    abstract function authenticate($username, $password);
    abstract function login($user, $bk);
    abstract static function getUser(); //Validates  authenticated users.
    abstract function getAllowedBackends($userid);
    abstract protected function getAuthKey($user);
    abstract static function signOut($user);
}
