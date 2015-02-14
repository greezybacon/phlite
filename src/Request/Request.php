<?php

namespace Phlite\Request;

use Phlite\Dispatch\BaseHandler;
use Phlite\Forms;
use Phlite\Http;
use Phlite\Io;
use Phlite\Util;

class Request
extends Io\BufferedInputStream {

    // Encoding of the request, usually indicated by the remote client in
    // the Accept-Charset header
    var $charset;

    // URL Request information
    var $method;
    var $host;
    var $port;
    var $url;
    var $query;
    var $fragment;
    var $path_info;
    
    var $GET;
    var $POST;
    var $META;
    var $COOKIES;
    
    var $handler;
	var $application;
    var $dispatcher;
    
    private static $current_request;
	
    function __construct($handler) {
        // Support HTTP method emulation with the _method GET argument
        if (isset($_REQUEST['_method']))
            $this->method = strtoupper($_REQUEST['_method']);
        else
            $this->method = strtoupper($_SERVER['REQUEST_METHOD']);
        
        // GET and POST vars
        $this->GET = new Http\QueryArray($_GET);
        $this->POST = new Http\QueryArray($_POST);
        $this->META = new Http\HeaderArray();
        $this->COOKIES = new Util\ArrayObject($_COOKIE);
            
        // Request as a file
        parent::__construct('php://input');
        
        $this->handler = $handler;
        
        self::$current_request = $this;
    }

    /**
     * Returns TRUE if the request is being serviced over an encrypted
     * connection.
     */
    function isHttps() {
        return (isset($_SERVER['HTTPS'])
                && strtolower($_SERVER['HTTPS']) == 'on')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
                && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https');
    }
    
    function isCli() {
        return !strcasecmp(substr(php_sapi_name(), 0, 3), 'cli')
            // Fallback when php-cgi binary is used via cli
            || (!isset($_SERVER['REQUEST_METHOD'])
                && !isset($_SERVER['HTTP_HOST']));
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
        return $this->handler->getPathInfo();
    }
    
    // Fetch the domain or host part of the Host header
    function getHost() {
        if (!isset($this->host)) {
            list ($this->host, $this->port) = explode(':', $_SERVER['HTTP_HOST'], 2);
        }
        return $this->host;
    }
    
    function getPort() {
        $this->getHost();
        return $this->port ?: $_SERVER['SERVER_PORT'];
    }
    
    // Useful for cookie domains
    function getCookieDomain() {
        if (Forms\Validation::isIp($this->host))
            return $this->host.':'.$this->port;
        
        return $this->host;
    }
    
    // Discover the root path of this project. Useful for static inclusions
    // and cookie path settings
    function getRootPath() {
        
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
        return $this->getProject()->getSettings();
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
    
    function getProject() {
        return $this->handler->getProject();
    }
    
    static function getCurrent() {
        return self::$current_request;
    }
    
    function setDispatcher($dispatcher) {
        $this->dispatcher = $dispatcher;
    }
    function getDispatcher() {
        return $this->dispatcher;
    }
}
