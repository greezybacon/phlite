<?php
    
namespace Phlite\Template\Loader;

abstract class BaseLoader { 
    
    // Returns a Twig_LoaderInterface instance
    static function getLoader($request) {
    }
}