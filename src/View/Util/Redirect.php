<?php
    
namespace Phlite\View\Util;

use Phlite\Dispatch\Reverser;
use Phlite\View\BaseView;
use Phlite\Request\HttpRedirect;

class Redirect extends BaseView {

    var $location;

    function __construct($location) {
        $this->location = $location;
    }
    
    function __invoke($request, $location=false) {
        $location = $location ?: $this->location;
        if (class_exists($location) || function_exists($location)) {
            // TODO: Lookup URL from this view
            $reverser = new Reverser();
            $location = $reverser->reverse($location);
        }
        return new HttpRedirect($location);
    }
}