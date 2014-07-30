<?php

namespace Phlite\Io;

interface OutputStream {

    function write($what);
    function writeline($line);
    function writelines($sequence);
    
    function seek($offset, $whence=false);
    function tell();

    function flush();
    function close();
}
