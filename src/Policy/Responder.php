<?php

namespace Phlite\Policy;

use Phlite\Http\Exception\HttpException;

class Responder extends HttpException {
    function __construct() {
        parent::__construct();
    }
    
    function getResponse($request) {
        
    }
}