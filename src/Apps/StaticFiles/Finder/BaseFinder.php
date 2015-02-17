<?php

namespace Phlite\Apps\StaticFiles\Finder;
    
abstract class BaseFinder
implements \IteratorAggregate {
    
    /**
     * function: locate
     *
     * Locate a requested static file.
     *
     * Parameters:
     * path - relative path requested in the URL for a static file.
     */
    abstract function locate($path);
    
    /**
     * iterPaths
     *
     * Convenience method to iterate over a list of directory paths and
     * returns an iterator to yield the containing files.
     *
     * Parameters:
     * paths - <array> list of directory paths to search for files
     *
     * Returns:
     * <Iterator> of files contained in the path list
     */
    function iterPaths($paths=array()) {
        $files = array();
        foreach ($paths as $P) {
            $relative = strlen($P)+1;
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($P, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($it as $F) {
                if (!$F->isFile())
                    continue;
                $files[] = new StaticFile(substr($F, $relative), $P);
            }
        }
        return new \ArrayIterator($files);
    }
}