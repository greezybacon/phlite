<?php

namespace Phlite\Io;

use Phlite\Io\BufferedInputStream;
use Phlite\Io\OutputStream;

/**
 * StringIO
 *
 * This class is used to provide a simple wrapper to read and write to a
 * temporary memory space. The stream supports the InputStream and
 * OutputStream interfaces which provide simple interface to reading,
 * writing and seeking. Use the ::getResouce() to get access to the 
 * underlying stream for direct reading and writing. Also, use ::getValue()
 * to convert the entire buffer to a single string value.
 */
class StringIo extends BufferedInputStream
    implements OutputStream {

    protected $stream;
    protected $closed = false;
    
    function __construct($eol=PHP_EOL) {
        $this->eol = $eol;
        parent::__construct('php://temp'), $eol);
    }
    
    // OutputStream Interface
    function write($what) {
        if ($this->closed)
            throw new Exception('Stream alread closed');
        return fwrite($this->stream, $what);
    }
    function writeline($line) {
        return $this->write($line.$this->eol);
    }
    function writelines($sequence) {
        foreach ($sequence as $line)
             $this->writeline($line);
    }
    function seek($offset, $whence=false) {
        if ($this->closed)
            throw new Exception('Stream already closed');
        return parent::seek($offset, $whence);
    }
    function close() {
        $this->closed = true;
    }
    
    function read($bytes=0) {
        if ($this->closed)
            throw new Exception('Stream already closed');
        return parent::read($bytes);
    }
    
    function reset() {
        parent::__construct($this->stream, $eol);
    }
    
    function getResource() {
        return $this->stream;
    }
    function getValue() {
        $this->rewind();
        return $this->read();
    }
    function __toString() {
        return $this->getValue();
    }
}
