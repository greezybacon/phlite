<?php

namespace Phlite\Dispatch;

interface Handler {

    function getRequest();

    /**
     * Process the request object and return a response object
     */
    function getResponse($request);

    /**
     * Processes URL path information (ususally the PATH_INFO veriable from
     * the HTTP server software
     */
    function getPathInfo();
}
