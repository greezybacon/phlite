<?php

namespace Phlite\Text;

abstract class CodecInfo {

    static $name = false;

    abstract function encode($what, $errors=false);

    abstract function decode($what, $errors=false);
}
