<?php

namespace Phlite\Text;

abstract class CodecInfo {

    var $name = false;

    abstract function encode($what, $errors=false);

    abstract function decode($what, $errors=false);
}
