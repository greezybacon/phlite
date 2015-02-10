<?php

namespace Phlite\Request;

use Phlite\Template\Exception\TemplateNotFound;
use Phlite\Template\Extension;
use Phlite\Template\TemplateContext;

class TemplateResponse {

    var $template;
    var $context;
    
    function __construct($template, array $context=array()) {
        $this->template = $template;
        $this->context = new TemplateContext($context);
    }

    function render($request) {
        $start = microtime(true);
        // Setup the template loading process to find the requested template
        $loader = $this->_getLoader($request);

        // Load and render the template
        $env = new \Twig_Environment($loader, [
            'charset' => $request->getCharset(),
        ]);
        $env->addExtension(new Extension\Url())
        if (!($template = $env->loadTemplate($this->template)))
            throw new TemplateNotFound($this->template);

        // Run template context processors to infleuence the current context
        $context = $this->_getContext($request);

        return new HttpResponse($template->render($context->asArray()));
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
        foreach ($request->getProject()->getTemplateContexts() as $TCP) {
            $I = new $TCP;
            $base->update($I->getContext($request));
        }
        return $base;
    }
}
