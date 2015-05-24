<?php

namespace Phlite\Io;

use Phlite\Cli;

class OutputStream {
    protected $eol;
    protected $stream;
    protected $closed = false;

    function __construct($stream, $line_ending=false) {
        $this->eol = $line_ending ?: PHP_EOL;
        
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
        return $this->write($line . $this->eol);
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
    
    // Terminal functions
    function isatty() {
        static $posix;
        if (!isset($posix))
            $posix = function_exists('posix_isatty');
        if ($posix)
            return posix_isatty($this->stream);
        
        return false;
    }
    function getTermInfo() {
        if (!$this->isatty())
            return Cli\Terminfo::dumb();

        return Cli\Terminfo::forTerminal();
    }
}
