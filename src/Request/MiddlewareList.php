<?php

namespace Phlite\Request;

use Phlite\Request\HttpResponse;
use Phlite\Util\Queue;

class MiddlewareList extends Queue {

    function processRequest($request) {
        foreach ($this as $mw) {
            $resp = $mw->processRequest($request);
            if ($resp && $resp instanceof HttpResponse)
                return $resp;
        }
    }

    function processView($request, $view) {
        foreach ($this as $mw) {
            $mw->processView($request, $view); 
        }
    }   

    function processTemplateResponse($request, $response) {
        foreach ($this as $mw) {
            $mw->processTemplateResponse($request, $response);
        }
    }

    function processResponse($request, $response) {
        foreach ($this as $mw) {
            $mw->processResponse($request, $response);
        }
    }

    function reverse() {
        return new MiddlewareList(parent::reverse());
    }

}
