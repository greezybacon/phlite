<?php

namespace Phlite\Text;

interface String {

    // Wrappers for built-in string functions
    function substr($start, $length=false);
    function indexOf($needle);
    function ltrim($chars=false);
    function rtrim($chars=false);
    function trim($chars=false);
    function explode($sep, $max=false);
    function upper();
    function lower();
    function unpack($format);
    
    // Comparison
    function cmp($other);
    function equals($other);
    
    // Other useful string operations
    function append($what);
    function capitalize();
    function substrCount($needle);
    function endsWith($what);
    function splice($offset, $length, $repl='');
    function startsWith($what);
    function width();
    function wrap($width, $delimiter="\n", $cut=false);
    
    // Regular expressions
    function search($pattern); // returns count of matches
    function matches($pattern);
    function replace($pattern, $replacement, $max=false);
    function split($pattern, $max=false);
    
    // Hash-lib
    function hash($func); //  u('')->hash('md5')->encode('hex');
}