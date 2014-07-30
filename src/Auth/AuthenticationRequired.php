<?php

namespace Phlite\Auth;

interface AuthenticationRequired {
    function getRequiredRole();

    function getLoginView();
}
