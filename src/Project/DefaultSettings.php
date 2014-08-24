<?php

namespace Phlite\Project;

class DefaultSettings extends Settings {

    function __construct() {
        $this->update(array(
            'MIDDLEWARE_CLASSES' => array(
                'Phlite\Request\Middleware\DbMiddleware',
                'Phlite\Request\Middleware\SessionMiddleware',
                'Phlite\Messages\MessageMiddleware',
            ),
            'TEMPLATE_CONTEXT_PROCESSORS' => array(
                #'Phlite\Auth\TemplateContext',
                #'Phlite\Messages\TemplateContext',
            ),
            'TEMPLATE_LOADERS' => array(
                'Phlite\Template\Loader\Filesystem',
            ),
        ));
        call_user_func_array(array('parent', '__construct'), func_get_args());
    }

}