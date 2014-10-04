<?php

namespace Phlite\Db\Util;

class Q {
    const NEGATED = 0x0001;
    const ANY =     0x0002;

    var $constraints;
    var $flags;
    var $negated = false;
    var $ored = false;

    function __construct($filter, $flags=0) {
        if (!is_array($filter))
            $filter = array($filter);
        $this->constraints = $filter;
        $this->negated = $flags & self::NEGATED;
        $this->ored = $flags & self::ANY;
    }

    function isNegated() {
        return $this->negated;
    }

    function isOred() {
        return $this->ored;
    }

    function negate() {
        $this->negated = !$this->negated;
        return $this;
    }

    static function not(array $constraints) {
        return new static($constraints, self::NEGATED);
    }

    static function any(array $constraints) {
        return new static($constraints, self::ANY);
    }
}