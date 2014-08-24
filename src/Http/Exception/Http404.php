<?php
    
namespace Phlite\Http\Exception;

class Http404 extends HttpException {
    static $status = 404;
}