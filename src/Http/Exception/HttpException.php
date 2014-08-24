<?php

namespace Phlite\Http\Exception;

use Phlite\Request\HttpResponse;
use Phlite\Request\TemplateResponse;

class HttpException extends \Exception {
    
    static $status = 500;
    
    function send() {
        $this->getResponse()->output();
    }
    
    function getResponse($request) {
        try {
			$T = new TemplateResponse($this::$status.'.html',
			 	['exception' => $this]);
        	$response = $T->render($request);
        	$response->status = static::$status;
        	return $response;
		}
		catch (\Twig_Error_Loader $ex) {
			return new HttpResponse($this->message, static::$status);
		}
    }
    
    static function refuseIfRequested($message='') {
        // Fetch filename of caller
        $bt = debug_backtrace(false);
        if (0 === strcasecmp(basename($_SERVER['SCRIPT_NAME']), 
                basename($bt[0]['file']))) {
            $ex = new static($message);
            $resp = $ex->getResponse();
            $resp->output();
            die();
        }
    }
}
