<?php
    
namespace Phlite\Template\Loader;

abstract class BaseLoader { 
    
    // Returns a Twig_LoaderInterface instance
    abstract static function getLoader($request);
}