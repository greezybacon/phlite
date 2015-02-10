<?php

namespace Phlite\Io;

use Phlite\Text\Bytes;

class BufferedOutputStream
extends OutputStream {

    var $le;
    var $buffer;
    var $threshold = 4096;

    function __construct($stream, $line_ending=false) {
        parent::__construct($stream, $line_ending);
        $this->buffer = new Bytes();
    }

    function flush() {
        parent::write($this->buffer->get());
        if (parent::flush())
            $this->buffer->set('');
    }

    function write($what, $length=false) {
        $this->buffer->append($what, $length);
        if ($this->buffer->length() > $this->threshold)
            $this->flush();
    }
}
