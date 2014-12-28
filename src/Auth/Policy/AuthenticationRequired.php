<?php

namespace Phlite\Auth\Policy;

use Phlite\Policy\Policy;

interface AuthenticationRequired extends Policy {
    function getRequiredRole();

    function getLoginView();
}