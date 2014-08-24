<?php

namespace Phlite\Http\Exception;

class MethodNotAllowed extends HttpException {
	static $status = 405;
	
    var $accept;

    function __construct($accept, $message=false) {
        parent::__construct($message ?: 'Method not allowed');
        $this->accept = $accept;
    }

    function getResponse($request) {
        $response = parent::getResponse($request);
        $response->headers['Accept'] = 
            is_array($this->accept) ? implode(',', $this->accept) : $this->accept;
		return $response;
    }
}
