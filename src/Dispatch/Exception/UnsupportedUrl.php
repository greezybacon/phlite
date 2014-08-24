<?php

namespace Phlite\Dispatch\Exception;

class UnsupportedUrl extends DispatchException {
    static $status = 404;
}