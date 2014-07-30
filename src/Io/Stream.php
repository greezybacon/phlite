<?php

namespace Phlite\Io;

class Stream
    implements InputStream, OutputStream {

    protected $stream;

    function __construct($stream) {
        $this->stream = $stream;
    }

    static function open($name, $mode='r') {
    }
}
