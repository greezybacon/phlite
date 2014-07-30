<?php

namespace Phlite\Logging\Handlers;

/**
 * A handler class which writes formatted logging records to disk files
 */
class FileHandler extends StreamHandler {

    function __construct($filename, $mode='a', $encoding=null, $delay=0) {
        $this->baseFilename = realpath($filename);
        $this->mode = $mode;
        $this->delay = $delay;
        if ($delay) {
            Handler::__construct();
            $this->stream = null;
        }
        else {
            parent::__construct($this->_open());
        }
    }

    function close() {
        if ($this->stream) {
            fflush($this->stream);
            fclose($this->stream);
        }
        parent::close();
    }

    /**
     * Open the current base file with the (original) mode and encoding.
     * Return the resulting stream.
     */
    function _open() {
        $stream = fopen($this->baseFilename, $this->mode);
        return $stream;
    }

    /**
     * Emit a record.
     *
     * If the stream was not opened because 'delay' was specified in the
     * constructor, open it before calling the superclass's emit.
     */
    function emit($record) {
        if ($this->stream == null) {
            $this->stream = $this->_open();
        }
        parent::emit($record);
    }
}
