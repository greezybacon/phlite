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
]);
$TEMPLATE_DIRS = [ 'Templates', ];
$TEMPLATE_CONTEXT_PROCESSORS = new ArrayObject([
    // 'Phlite\Auth\TemplateContext',
    // 'Phlite\Messages\TemplateContext',
]);
$TEMPLATE_LOADERS = new ArrayObject([
    'Phlite\Template\Loader\Filesystem',
    'Phlite\Template\Loader\Application',
]);

# Request handling ----------------------

$HANDLER = 'Phlite\Request\Handlers\ApacheHandler';
$MIDDLEWARE_CLASSES = new ArrayObject([
    'Phlite\Request\Middleware\CoreMiddleware',
    'Phlite\Core\Session\SessionMiddleware',
    'Phlite\Security\Features\Csrf\Middleware',
    'Phlite\Messages\MessageMiddleware',
    'Phlite\Apps\StaticFiles\Middleware',
]);

# Other stuff ---------------------------

$SESSION_BACKEND = 'Phlite\Core\Session\Storage\PHPSession';
$SESSION_COOKIE_NAME = 'PHLITE_SESSION';
$SESSION_HTTPONLY = false;

$STATIC_URL = '/static/';
$STATICFILES_STORAGE = 'Phlite\Apps\StaticFiles\Storage\FilesStorage';
$STATICFILES_FINDERS = new ArrayObject([
    'Phlite\Apps\StaticFiles\Finder\Application'
]);
