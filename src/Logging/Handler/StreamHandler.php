<?php

namespace Phlite\Logging\Handler;

use Phlite\Logging;

/**
 * A handler class which writes logging records, appropriately formatted,
 * to a stream. Note that this class does not close the stream, as
 * php://stdout or php://stderr may be used.
 */
class StreamHandler extends Logging\Handler {

    function __construct($stream=null) {
        parent::__construct();
        $this->stream = fopen($stream ?: 'php://stderr', 'w');
    }

    function flush() {
        fflush($this->stream);
    }

    /**
     * Emit a record.
     *
     * If a formatter is specified, it is used to format the record.
     * The record is then written to the stream with a trailing newline.  If
     * exception information is present, it is formatted using
     * traceback.print_exception and appended to the stream.  If the stream
     * has an 'encoding' attribute, it is used to determine how to do the
     * output to the stream.
     */
    function emit($record) {
        try {
            $msg = $this->format($record);
            $stream = $this->stream;
            $fs = "%s\n";
            fwrite($this->stream, sprintf($fs, $msg));
            $this->flush();
        }
        catch (Exception $ex) {
            $this->handleError($record, $ex);
        }
    }
}
