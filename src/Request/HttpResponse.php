<?php

namespace Phlite\Request;

class HttpResponse extends BaseResponse {

    var $status;
    var $type;
    
    var $etag;
    var $last_modified;
    var $cache_ttl;
    protected $iscacheable = false;

    function __construct($stream, $status=200, $type='text/html') {
        $this->body = $stream;
        $this->status = $status;
        $this->type = $type;
    }

    function header_code_verbose($code) {
        switch($code):
        case 100: return '100 Continue';
        case 200: return '200 OK';
        case 201: return '201 Created';
        case 204: return '204 No Content';
        case 205: return '205 Reset Content';
        case 304: return '304 Not Modified';
        case 400: return '400 Bad Request';
        case 401: return '401 Unauthorized';
        case 403: return '403 Forbidden';
        case 404: return '404 Not Found';
        case 405: return '405 Method Not Allowed';
        case 416: return '416 Requested Range Not Satisfiable';
        case 422: return '422 Unprocessable Entity';
        default:  return '500 Internal Server Error';
        endswitch;
    }
    
    function getLength() {
        return ($this->body instanceof BaseString)
            ? $this->body->getLength() : strlen($this->body);
    }
    
    function getEncoding() {
        return ($this->body instanceof BaseString)
            ? $this->body->getEncoding() : 'utf-8';
    }
    
    function setType($type) {
        $this->type = $type;
    }
    
    function getStatusCode() {
        if (!isset($this->cacheable)) {
            // Thanks, http://stackoverflow.com/a/1583753/1025836
            // Timezone doesn't matter here — but the time needs to be
            // consistent round trip to the browser and back.
            if ($this->last_modified) {
                if (!is_numeric($this->last_modified))
                    $this->last_modified = strtotime($this->modified." GMT");
                if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $this->last_modified) {
                    $this->status = 304;
                }
                header("Last-Modified: ".@date('D, d M Y H:i:s', $this->last_modified)." GMT");
            }
            if ($this->etag) {
                header('ETag: "'.$this->etag.'"');
                if (@trim($_SERVER['HTTP_IF_NONE_MATCH'], '"') == $this->etag) {
                    $this->status = 304;
                }
            }
            $this->iscacheable = $this->status == 304;
        }
        return $this->status;
    }
    
    function output($request) {        
        if ($this->cache_ttl) {
            header("Cache-Control: private, max-age={$this->cache_ttl}");
            header('Expires: ' . @date('D, d M Y H:i:s', gmdate('U') + $this->cache_ttl)." GMT");
            header('Pragma: private');
        }
        
        // Consider cacheing
        $this->getStatusCode();
        
        // XXX: This breaks the CGI interface rules
        header('HTTP/1.1 '.self::header_code_verbose($this->status));
		header('Status: '.self::header_code_verbose($this->status));
        
        if ($this->iscacheable)
            // Cached. Don't send the usual output
            return;
        
        if (strpos($this->type, 'charset=') === false)
            $this->type .= '; charset=' . $this->getEncoding();
		header("Content-Type: {$this->type}");
        # header('Content-Length: '.$this->getLength());
        $this->sendHeaders();        
        $this->sendBody();
    }
    
    function sendBody() {
        echo (string) $this->body;
    }

    static function forStatus($status, $message=null, $type='text/html') {
        return new HttpResponse($message ?: '', $status, $type);
    }
    
    function cacheable($etag, $modified, $ttl=3600) {
        $this->etag = $etag;
        $this->last_modified = $modified;
        $this->cache_ttl = $ttl;
    }
    
    function useClientCache() {
        
    }
}
