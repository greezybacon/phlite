<?php

namespace Phlite\Io;

interface InputStream
    extends \Iterator {

    function read($size=0);
    function readline();
    function readlines();

    function seek($offset, $whence=false);
    function tell();

    function close();
}
