<?php

namespace Phlite\View;

use Phlite\Request\HttpResponse;

class ErrorView extends Viewable {

    var $code;
    var $message;

    function __construct($code, $message) {
        $this->code = $code;
        $this->message = $message;
    }

    function __invoke($request) {
        return HttpResponse::forStatus($this->code, $this->message);
    }
}
