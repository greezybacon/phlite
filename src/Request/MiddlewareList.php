<?php

namespace Phlite\Request;

use Phlite\Request\HttpResponse;

class MiddlewareList extends ArrayObject {

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

    function reverse() {
        $items = array_reverse(array($this));
        return new MiddlewareList($items);
    }

}
