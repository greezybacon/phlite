<?php

namespace Phlite\Request;

/**
 * Class: Middleware
 *
 * This middleware infrastructure is based off of Django's middle stack.
 */
abstract class Middleware {
    
    function processRequest($request) {
    }

    function processView($request, $func, $args) {
    }

    function processTemplateResponse($request, $response) {
    }

    function processResponse($request, $response) {
    }

    function processException($request, $exception) {
    }
}
