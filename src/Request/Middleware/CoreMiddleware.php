<?php

namespace Phlite\Request\Middleware;

use Phlite\Logging\Log;
use Phlite\Request;

class CoreMiddleware extends Request\Middleware {
    var $logger;
    
    function __construct() {
        $this->logger = Log::getLogger('phlite.request');
    }
    
    function processResponse($request, $response) {
        // Log the request
        $this->logger->info('', ['extra' => [
            'verb' => $request->getMethod(),
            'ip' => $request->META->getRemoteAddr(),
            'path' => $request->getPath(),
            'status' => $response ? $response->getStatusCode() : '?',
        ]]);
    }
    
    function processException($request, $ex) {
        $this->logger->exception("\n{exname}: {exmessage}", $ex, ['extra' => [
            'verb' => $request->getMethod(),
            'ip' => $request->META->getRemoteAddr(),
            'path' => $request->getPath(),
            'status' => 500,
        ],
        'exname' => get_class($ex),
        'exmessage' => $ex->getMessage(),
        ]);
    }
    
}