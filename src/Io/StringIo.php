<?php

namespace Phlite\Io;

use Phlite\Io\InputStream;
use Phlite\Io\OutputStream;

class StringIo
    implements InputStream, OutputStream {

    protected $stream;

    function __construct() {
        $this->stream = fopen('php://temp');
    }
}
