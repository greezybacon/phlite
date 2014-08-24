<?php

namespace Phlite\Request;

use Phlite\Dispatch\BaseHandler;

class Request {

    // Encoding of the request, usually indicated by the remote client in
    // the Accept-Charset header
    var $charset;

    // URL Request information
    var $method;
    var $url;
    var $query;
    var $fragment;
    var $path_info;
    
    var $handler;
	var $application;
	
    function __construct($handler) {
        // Support HTTP method emulation with the _method GET argument
        if (isset($_REQUEST['_method']))
            $this->method = strtoupper($_REQUEST['_method']);
        else
            $this->method = strtolower($_SERVER['REQUEST_METHOD']);
        $this->handler = $handler;
    }

    /**
     * Returns TRUE if the request is being serviced over an encrypted
     * connection.
     */
    function isSecure() {
        return (isset($_SERVER['HTTPS'])
                && strtolower($_SERVER['HTTPS']) == 'on')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
                && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https');
    }

    /**
     * Retrieve the remote client address. This is usually REMOTE_ADDR but
     * can be something else based on the server software configuration.
     */
    function getRemoteAddress() {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Take the left-most item for X-Forwarded-For
            return trim(array_pop(
                explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])));
        }
        else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    function getPath() {
        if (!isset($this->path_info)) {
            $this->path_info = @$_SERVER['PATH_INFO']
                ?: @$_SERVER['ORIG_PATH_INFO']
                ?: @$_SERVER['REDIRECT_PATH_INFO'];
        }
        return $this->path_info;
    }

    function accepts($content_type) {
    }

    function acceptsLanguage($lang) {
    }
    
    function getCharset() {
        return $this->charset ?: 'utf-8';
    }

    /**
     * Retrieve compiled settings from the project
     */
    function getSettings() {
        return $this->handler->getProject()->getSettings();
    }

    /**
     * Retrieve the application handling the request. This is discovered via
     * the urls exported by the application.
     *
     * Caveats:
     * This function may return FALSE if no application is mapped to the
     * current URL or if the url is defined in the root urls file.
     */
    function getApplication() {
		return $this->application;
    }
	function setApplication(Application $app) {
		$this->application = $app;
	}
}
