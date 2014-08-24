<?php

namespace Phlite\Dispatch\Exception;

use Phlite\Http\Exception\HttpException;

class DispatchException extends HttpException {
    static $status = 422;
}