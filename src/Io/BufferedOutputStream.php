<?php

namespace Phlite\Io;

class BufferedOutputStream
        extends SplFileObject
        implements OutputStream {

    var $le;
    var $buffer;
    var $threshold = 4096;

    function __construct($line_ending=false) {
        $this->le = $line_ending ?: "\n";
        $this->buffer = new Bytes();
    }

    function flush() {
        $this->fwrite($this->buffer->get());
        $this->fflush();
    }

    function write($what, $length=false) {
        $this->buffer->append($what, $length);
        if ($this->buffer->length() > $this->threshold)
            $this->flush();
    }

    function writeline($line) {
        $this->write($line);
        $this->write($this->le);
    }
}
