<?php

namespace Phlite\Request\Exception;

use Phlite\View\ErrorView;

class Http404 extends Exception {

    var $view;

    function __construct($message) {
        $this->view = ErrorView::lookup(404);
    }

}
