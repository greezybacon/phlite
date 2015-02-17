<?php

namespace Phlite\Apps\StaticFiles\Finder;

/**
 * Class: StaticFile
 *
 * Simple representation of a found static file. This is used by the
 * static file finders in order to represent the relative, base, and full
 * paths of single static file identified.
 */
class StaticFile /* extends SplFileObject ? */ {
    
    var $base;
    var $path;
    
    function __construct($path, $base) {
        $this->path = ltrim($path, '/\\');
        $this->base = rtrim($base, '/\\');
    }
    
    function getRelativePath() {
        return $this->path;
    }
    
    function getFullPath() {
        return $this->base . '/' . $this->path;
    }
    
    function __toString() {
        return $this->getFullPath();
    }
}