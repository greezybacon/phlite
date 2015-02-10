<?php

namespace Phlite\Mail\Parse\Tnef;

use Phlite\Text\BytesView;

/**
 * References:
 * http://download.microsoft.com/download/1/D/0/1D0C13E1-2961-4170-874E-FADD796200D9/%5BMS-OXTNEF%5D.pdf
 * http://msdn.microsoft.com/en-us/library/ee160597(v=exchg.80).aspx
 * http://sourceforge.net/apps/trac/mingw-w64/browser/trunk/mingw-w64-headers/include/tnef.h?rev=3952
 */
class TnefStreamReader implements \Iterator {
    const SIGNATURE = 0x223e9f78;

    var $pos = 0;
    var $length = 0;
    var $stream;
    var $current = true;

    var $options = array(
        'checksum' => true,
    );

    function __construct($stream, $options=array()) {
        if (is_array($options))
            $this->options += $options;

        $this->setStream($stream);
        
        // Read header
        if (self::SIGNATURE != $this->_geti(32))
            throw new TnefException("Invalid signature");

        $this->_geti(16); // Attach key

        $this->next(); // Process first block
    }
    
    protected function setStream($stream) {
        if (is_string($stream))
            $stream = new BytesView($stream);
        $this->stream = $stream;
        $this->pos = 0;
        $this->length = $stream->length;
    }

    protected function _geti($bits) {
        $bytes = $bits / 8;

        switch($bytes) {
        case 1:
            $value = ord($this->stream[$this->pos]);
            break;
        case 2:
            list(,$value) = unpack('v', $this->stream->substr($this->pos, 2));
            break;
        case 4:
            list(,$value) = unpack('V', $this->stream->substr($this->pos, 4));
            break;
        }
        $this->pos += $bytes;
        return $value;
    }
    protected function _getp($bytes, $unpack) {
        // Avoid creating an entire BytesView just to convert to a
        // string for unpacking
        $value = unpack($unpack, $this->stream->substr($this->pos, $bytes));
        $this->pos += $bytes;

        return $value;
    }

    protected function _getx($bytes) {
        $value = $this->stream->slice($this->pos, $bytes);
        $this->pos += $bytes;

        return $value;
    }

    function check($block) {
        $sum = 0; $bytes = $block['data']->length; $bs = 1024;
        for ($i=0; $i < $bytes; $i+=$bs) {
            $b = unpack('C*', $block['data']->substr($i, min($bs, $bytes-$i)));
            $sum += array_sum($b);
            $sum = $sum % 65536;
        }
        if ($block['checksum'] != $sum)
            throw new TnefException('Corrupted block. Invalid checksum');
    }

    function next() {
        if ($this->length - $this->pos < 11) {
            $this->current = false;
            return;
        }

        $this->current = array(
            'level' => $this->_geti(8),
            'type' => $this->_geti(32),
            'length' => $length = $this->_geti(32),
            'data' => $this->_getx($length),
            'checksum' => $this->_geti(16)
        );
            
        if ($this->options['checksum'])
            $this->check($this->current);
    }

    function current() {
        return $this->current;
    }

    function key() {
        return $this->current['type'];
    }

    function valid() {
        return (bool) $this->current;
    }

    function rewind() {
        // Skip signature and attach-key
        $this->pos = 6;
    }
}
