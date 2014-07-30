<?php

namespace Phlite\Text;

use Phlite\Text\Bytes;

/**
 * Class: BytesView
 *
 * BytesView is a simple wrapper over the base string classes which allows
 * for viewing and manipulating string content without copying the string
 * data to other memory.
 */
class BytesView extends Bytes {
    var $length;
    private $start;
    private $end;

    function __construct(&$string, $start=0, $end=false) {
        parent::__construct($string);
        $this->start = $start;
        $this->end = $end;
        $this->length = $end ? $end - $start : strlen($string) - $start;
    }   

    function __toString() {
        return $this->end
            ? substr($this->string, $this->start, $this->end - $this->start)
            : substr($this->string, $this->start);
    }   

    function slice($start, $end) {
        return new static($this->string, $start, $end);
    }

    function substr($start, $length=false) {
        return new static($this->string, $this->start + $start,
            $end ? min($this->start + $end, $this->end ?: PHP_INT_MAX) : $this->end);
    }

    function explode($token) {
        $ltoken = strlen($token);
        $windows = array();
        $offset = $this->start;
        for ($i = 0;; $i++) {
            $windows[$i] = array('start' => $offset);
            $offset = strpos($this->string, $token, $offset);
            if (!$offset || ($this->end && $offset >= $this->end))
                break;

            // Enforce local window
            $windows[$i]['stop'] = min($this->end ?: $offset, $offset);
            $offset += $ltoken;
            if ($this->end && $offset > $this->end)
                break;
        }

        $parts = array();
        foreach ($windows as $w) {
            $parts[] = new static($this->string, $w['start'], @$w['stop'] ?: false);
        }   
        return $parts;
    }   

    function unpack($format, $length=false) {
        return unpack($this, $format);
    }
}
