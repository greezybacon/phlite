<?php

namespace Phlite\Template\Exception;

class TemplateNotFound extends Exception {
    function __construct($template) {
        parent::__construct($template . ': Could not find template');
    }
}