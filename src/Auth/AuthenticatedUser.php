<?php

namespace Phlite\Auth;

abstract class AuthenticatedUser {
    //Authorization key returned by the backend used to authorize the user
    private $authkey;

    // Get basic information
    abstract function getId();
    abstract function getUsername();
    abstract function getRole();

    //Backend used to authenticate the user
    abstract function getAuthBackend();

    //Authentication key
    function setAuthKey($key) {
        $this->authkey = $key;
    }

    function getAuthKey() {
        return $this->authkey;
    }

    // logOut the user
    function logOut() {

        if ($bk = $this->getAuthBackend())
            return $bk->signOut($this);

        return false;
    }
}
