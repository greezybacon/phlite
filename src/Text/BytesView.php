<?php

namespace Phlite\Text;

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

    function __construct($string, $start=0, $end=false) {
        parent::__construct($string);
        $this->start = $start;
        $this->end = $end;
        $this->length = $end ? $end - $start : $this->length - $start;
    }   

    function __toString() {
        return substr($this->string, $this->start, $this->length) ?: '';
    }   

    function substr($start, $length=false) {
        return substr($this->string, $this->start + $start, $length);
    }

    function slice($start, $length=false) {
        return new static($this->string, $this->start + $start,
            $length ? min($this->start + $start + $length, $this->end ?: PHP_INT_MAX) : $this->end);
    }
    
    function truncate($length) {
        if ($length < 0)
            throw new \InvalidArgumentException('$length must be positive');
        $this->end = $this->start + $length;
        $this->length = $this->end - $this->start;
        return $this;
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
    
    // ---- ArrayAccess interface -----------------------------
    function offsetGet($offset) {
        return $this->string[min($offset + $this->start, $this->end)];
    }
}
