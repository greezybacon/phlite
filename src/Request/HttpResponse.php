<?php

namespace Phlite\Request;

class HttpResponse extends BaseResponse {

    var $headers = array();

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

    function output() {
        $charset = ($this->body instanceof BaseString)
            ? $this->body->getEncoding() : 'utf-8';
        $length = ($this->body instanceof BaseString)
            ? $this->body->getLength() : strlen($this->body);

        header('HTTP/1.1 '.self::header_code_verbose($this->status));
		header('Status: '.self::header_code_verbose($this->type)."\r\n");
		header("Content-Type: $this->type; charset=$charset\r\n");
        //header('Content-Length: '.$length."\r\n\r\n
		foreach ($this->headers as $name=>$content)
			header("$name: $content\r\n");
       	echo (string) $this->body;
    }

    static function forStatus($status, $message=null, $type='text/html') {
        return new HttpResponse($message ?: '', $status, $type);
    }
}
