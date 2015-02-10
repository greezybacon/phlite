<?php

namespace Phlite\Io;

class OutputStream {
    protected $le;
    protected $stream;
    protected $closed = false;

    function __construct($stream, $line_ending=false) {
        $this->le = $line_ending ?: "\n";
        
        if (is_string($stream))
            $stream = fopen($stream, 'wb');
        
        $this->stream = $stream;
    }
    
    function __destruct() {
        if (!$this->closed) {
            $this->flush();
            $this->close();
        }
    }

    function write($what) {
        return fwrite($this->stream, $what);
    }
    function writeline($line) {
        return $this->write($line . $this->le);
    }
    function writelines($sequence) {
        foreach ($sequence as $line)
            $this->writeline($line);
    }
    
    function seek($offset, $whence=false) {
        return fseek($this->stream, $offset, $whence);
    }
    function tell() {
        return ftell($this->stream);
    }

    function flush() {
        return fflush($this->stream);
    }
    function close() {
        return fclose($this->stream);
        $this->closed = true;
    }
}
