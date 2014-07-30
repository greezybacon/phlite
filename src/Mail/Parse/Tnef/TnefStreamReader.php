<?php

namespace Phlite\Mail\Parse\Tnef;

/**
 * References:
 * http://download.microsoft.com/download/1/D/0/1D0C13E1-2961-4170-874E-FADD796200D9/%5BMS-OXTNEF%5D.pdf
 * http://msdn.microsoft.com/en-us/library/ee160597(v=exchg.80).aspx
 * http://sourceforge.net/apps/trac/mingw-w64/browser/trunk/mingw-w64-headers/include/tnef.h?rev=3952
 */
class TnefStreamReader implements Iterator {
    const SIGNATURE = 0x223e9f78;

    var $pos = 0;
    var $length = 0;
    var $streams = array();
    var $current = true;

    var $options = array(
        'checksum' => true,
    );

    function __construct($stream, $options=array()) {
        if (is_array($options))
            $this->options += $options;

        $this->push($stream);

        // Read header
        if (self::SIGNATURE != $this->_geti(32))
            throw new TnefException("Invalid signature");

        $this->_geti(16); // Attach key

        $this->next(); // Process first block
    }

    protected function push(&$stream) {
        $this->streams[] = array($this->stream, $this->pos, $this->length);
        $this->stream = &$stream;
        $this->pos = 0;
        $this->length = strlen($stream);
    }

    protected function pop() {
        list($this->stream, $this->pos, $this->length) = array_pop($this->streams);
    }

    protected function _geti($bits) {
        $bytes = $bits / 8;

        switch($bytes) {
        case 1:
            $value = ord($this->stream[$this->pos]);
            break;
        case 2:
            $value = unpack('vval', substr($this->stream, $this->pos, 2));
            $value = $value['val'];
            break;
        case 4:
            $value = unpack('Vval', substr($this->stream, $this->pos, 4));
            $value = $value['val'];
            break;
        }
        $this->pos += $bytes;
        return $value;
    }

    protected function _getx($bytes) {
        $value = substr($this->stream, $this->pos, $bytes);
        $this->pos += $bytes;

        return $value;
    }

    function check($block) {
        $sum = 0; $bytes = strlen($block['data']); $bs = 1024;
        for ($i=0; $i < $bytes; $i+=$bs) {
            $b = unpack('C*', substr($block['data'], $i, min($bs, $bytes-$i)));
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
