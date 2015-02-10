<?php

namespace Phlite\Request;

use Phlite\Io;
use Phlite\Text;

class StreamResponse extends HttpResponse {
    
    /**
     * Fetches a block to be sent to the client. Either a PHP string or an
     * instance of Unicode. The latter will allow automatic conversion to 
     * the response character set.
     *
     * Return boolean false or throw Io\Eof to indicate end of output.
     */
    function read() {
        if ($this->stream instanceof Io\InputStream)
            return $this->stream->read();
    }
    
    function sendBody() {
        try {
            while ($block = $this->read()) {
                if ($block instanceof Text\Unicode)
                    $block = $block->get($this->getEncoding());
                echo $block;
            }
        }
        catch (Io\Eof as $e) {
        }
    }
}