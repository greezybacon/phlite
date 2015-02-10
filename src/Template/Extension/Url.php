<?php

namespace Phlite\Template\Extension;

class Url
extends \Twig_Extension {
    
    function getFunctions() {
        return array(
            new \Twig_SimpleFunction('url', array($this, 'getUrl'));
        );
    }
    
    function getUrl() {
        $args = func_get_args();
        $class = array_shift($args);

        $request = Request::getCurrent();
        return $request->getDispatcher()->reverse($class, $args);
    }
}
    