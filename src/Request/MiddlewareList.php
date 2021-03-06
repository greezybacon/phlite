<?php

namespace Phlite\Request;

use Phlite\Request\HttpResponse;
use Phlite\Util\ListObject;

class MiddlewareList extends ListObject {

    function processRequest($request) {
        foreach ($this as $mw) {
            $resp = $mw->processRequest($request);
            if ($resp && $resp instanceof Response)
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
    
    function processException($hander, $ex) {
        $request = Request::getCurrent();
        foreach ($this as $mw) {
            $response = $mw->processException($request, $ex);
            if ($response && $response instanceof Response)
                return $response;
        }
        if (method_exists($ex, 'getResponse'))
            return $ex->getResponse($request);
    }

    function reverse() {
        return new MiddlewareList(parent::reverse());
    }

}
