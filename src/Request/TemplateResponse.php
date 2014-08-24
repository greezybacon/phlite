<?php

namespace Phlite\Request;

use Phlite\Template\Exception\TemplateNotFound;

class TemplateResponse {

    var $template;
    var $context;
    
    function __construct($template, array $context=array()) {
        $this->template = $template;
        $this->context = $context;
    }

    function render($request) {
        // Setup the template loading process to find the requested template
        $loader = $this->_getLoader($request);

        // Load and render the template
        $env = new \Twig_Environment($loader, array(
            'charset' => $request->getCharset(),
        ));
        if (!($template = $env->loadTemplate($this->template)))
            throw new TemplateNotFound($this->template);

        // Run template context processors to infleuence the current context
        $context = $this->_getContext($request);

        return new HttpResponse($template->render($context));
    }

    function _getLoader($request) {
        $loaders = array();
        foreach ($request->getSettings()->get('TEMPLATE_LOADERS') as $LC) {
            if ($loader = $LC::getLoader($request)) {
                if (is_array($loader))
                    $loaders = array_merge($loaders, $loader);
                else
                    $loaders[] = $loader;
            }
        }
        return new \Twig_Loader_Chain($loaders);
    }

    function _getContext($request) {
        $base = $this->context;
        foreach ($request->getSettings()->get('TEMPLATE_CONTEXT_PROCESSORS') as $TCP) {
            $I = new $TCP;
            $base += $TCP->getContext($request);
        }
        return $base;
    }
}
