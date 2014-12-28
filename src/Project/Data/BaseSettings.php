<?php

use Phlite\Util\ArrayObject;

# These settings are the global, default, and initial settings for all
# projects. It's primarily useful for settings which are expected to have
# a value, yet are not added to the TemplateSettings file for new projects

$DEBUG = false;

# Mail and alerts settings ---------------

$EMAIL_BACKEND = 'Phlite\Mail\Backends\Smtp';

# Database settings ----------------------

# Applications --------------------------

$APPLICATIONS = new ArrayObject([
    'Phlite\Apps\Db\Application',
]);
$TEMPLATE_DIRS = [ 'Templates', ];
$TEMPLATE_CONTEXT_PROCESSORS = new ArrayObject([
    'Phlite\Auth\TemplateContext',
    'Phlite\Messages\TemplateContext',
]);
$TEMPLATE_LOADERS = new ArrayObject([
    'Phlite\Template\Loaders\Filesystem',
    'Phlite\Template\Loaders\AppDirectory',
]);

# Request handling ----------------------

$HANDLER = 'Phlite\Request\Handlers\ApacheHandler';
$MIDDLEWARE_CLASSES = new ArrayObject([
    'Phlite\Request\Middleware\DbMiddleware',
    'Phlite\Core\Session\SessionMiddleware',
    'Phlite\Security\Features\Csrf\Middleware',
    'Phlite\Messages\MessageMiddleware',
]);

# Other stuff ---------------------------
