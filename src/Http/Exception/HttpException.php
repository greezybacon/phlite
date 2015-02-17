<?php

namespace Phlite\Http\Exception;

use Phlite\Request\HttpResponse;
use Phlite\Request\Request;
use Phlite\Request\TemplateResponse;

class HttpException extends \Exception {
    
    static $status = 500;
    
    var $code;
    
    function __construct($message, $status=null) {
        parent::__construct($message);
        $this->code = isset($status) ? $status : static::$status;
    }
    
    function send() {
        $this->getResponse()->output();
    }
    
    function getResponse($request) {
        try {
			$T = new TemplateResponse($this->code.'.html',
			 	['exception' => $this]);
        	$response = $T->render($request);
        	$response->status = $this->code;
        	return $response;
		}
		catch (\Twig_Error_Loader $ex) {
			return new HttpResponse($this->message, static::$status);
		}
    }
    
    /**
     * refuseIfRequested
     *
     * Convenience method to halt the PHP engine if certain files are included
     * which should not be served directly by the HTTP server to the connected
     * client. This method calls die() after formally delivering an HTTP error
     * response to the client.
     */
    static function refuseIfRequested($status=null, $message='') {
        // Fetch filename of caller
        $bt = debug_backtrace(false);
        if (0 === strcasecmp(basename($_SERVER['SCRIPT_NAME']), 
                basename($bt[0]['file']))) {
            $ex = new static($message, $status);
            $request = Request::getCurrent() ?: new Request(null);
            $resp = $ex->getResponse($request);
            $resp->output($request);
            die();
        }
    }
}
