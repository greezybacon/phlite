<?php

namespace Phlite\Apps\StaticFiles\Finder;

abstract class BaseFinder
implements \IteratorAggregate {
    abstract function locate($path);
}