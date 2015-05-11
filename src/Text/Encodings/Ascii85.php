<?php

namespace Phlite\Text\Encodings;

use Phlite\Text\Bytes;
use Phlite\Text\Codec;
use Phlite\Text\CodecInfo;

class Ascii85
extends CodecInfo {
    static $name = 'Ascii85';
    
    static $alphabet =
        '!"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstu';
    static $zeros = 'z';
    static $spaces = 'y';

    var $A; 
    var $Z; 
    var $S;
    var $wrap;

    function __construct($wrap=80, $alphabet=null, $zeros=null, $spaces=null) {
        $this->wrap = $wrap;
        $this->A = $alphabet ?: static::$alphabet;
        $this->Z = $zeros ?: (strpos($alphabet, static::$zeros) === false
            ? static::$zeros : null);
        $this->S = $spaces ?: (strpos($alphabet, static::$spaces) === false
            ? static::$spaces : null);
    }   

    function encode($what, $errors=false) {
        $alphabet = $this->A;
        $zeros = $this->Z;
        $spaces = $this->S;

        $pad = 4 - (strlen($what) % 4);
        if ($pad)
            $what .= str_repeat("\000", $pad);

        $tuples = unpack('N*', $what);
        $last = count($tuples);
        foreach ($tuples as $i=>$t) {
            // No $zeros abbreviation if ending in all zeros
            if ($zeros && $t === 0 
                && (!$pad || $i !== $last)
            ) {
                $tuples[$i] = $zeros;
            }
            elseif ($spaces && $t === 0x20202020 
                && (!$pad || $i !== $last)
            ) {
                $tuples[$i] = $spaces;
            }
            else {
                $block = '';
                for ($k=0; $k<5; $k++) {
                    $block .= $alphabet[$t % 85];
                    $t /= 85;
                }
                $tuples[$i] = strrev($block);
            }
        }
 
        if ($pad && $tuples[$last] === $zeros)
            $output[$last] = str_repeat($alphabet[0], 5);
                
        // Strip padding from the output
        if ($pad)
            $tuples[$last] = substr($tuples[$last], 0, 5 - $pad);
        
        $output = implode('', $tuples);
        
        // Wrap if requested
        if ($this->wrap)
            $output = implode("\n", str_split($output, $this->wrap));
        
        return new Bytes($output);
    }

    function decode($what, $errors=false) {
        $alphabet = array_flip(str_split($this->A));
        $zeros = $this->Z;
        $spaces = $this->S;
        $output = array();
        $pos = 0;
        $length = strlen($what);

        while ($pos < $length) {
            if ($what[$pos] === $zeros) {
                $output[] = 0;
            }
            elseif ($what[$pos] === $spaces) {
                $output = 0x20202020;
                $size = 5;
            }
            else {
                $block = 0;
                $chunk = substr($what, $pos, 5);
                $size = strlen($chunk); // Save for later
                for ($i=0; $i<$size; $i++) {
                    $block = $block * 85 + $alphabet[$chunk[$i]];
                }
                $output[] = $block;
                $pos += $size;
            }
        }
        
        // Pad and trim last incomplete block
        if ($size < 5) {
            $tail = array_pop($output);
            for ($i=$size; $i<5; $i++) {
                $tail = $tail * 85 + 84;
            }
            list($tail) = pack('N', $tail);
        }
        
        array_unshift($output, 'N*');
        $output = call_user_func_array('pack', $output);
        
        // Append the truncated tail to the output
        if ($tail) {
            $output .= substr($tail, 0, $size - 1);
        }
        return new Bytes($output);
    }
}

class Z85
extends Ascii85 {
    static $alphabet = 
        '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.-:+=^!/*?&<>()[]{}@%$#';
    static $spaces = false;
    static $zeros = false;
}

class Rfc1924
extends Ascii85 {
    static $alphabet = 
        '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz!#$%&()*+-;<=>?@^_`{|}~';
    static $spaces = false;
    static $zeros = false;
}

Codec::register(function($encoding) {
    switch ($encoding) {
        case 'ascii85':
            return new Ascii85();
        case 'z85':
            return new Z85();
        case 'rfc1924':
            return new Rfc1924();
    }
});
