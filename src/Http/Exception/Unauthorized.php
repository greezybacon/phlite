<?php
    
namespace Phlite\Http\Exception;

class Unauthorized extends HttpException {
    static $status = 403;
}