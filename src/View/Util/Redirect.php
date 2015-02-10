<?php
    
namespace Phlite\View\Util;

use Phlite\Dispatch\Reverser;
use Phlite\View\BaseView;
use Phlite\Request\HttpRedirect;

class Redirect extends BaseView {
    
    function __invoke($request, $location) {
        if (class_exists($location) || function_exists($location)) {
            // TODO: Lookup URL from this view
            $reverser = new Reverser();
            $location = $reverser->reverse($location);
        }
        return new HttpRedirect($location);
    }
}