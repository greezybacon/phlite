<?php

namespace Phlite\Request;

class TemplateResponse {

    var $template;
    var $context;
    
    function __construct($template, $context=array()) {
        $this->template = $this->getTemplate($template);
        if (!$context instanceof ArrayObject)
            $this->context = new ArrayObject($context);
    }

    function render($request) {
        $this->context['request'] = $request;
        return $this->template->render();
    }
}
