<?php

# This is the project configuration for a Phlite project
# {{ project.name }}
# Author: {{ project.creater }}
# Created: {{ project.created }}

# Variables defined in this file will become indeces in the settings array
# for the project

$DEBUG = true;

# Mail and alerts settings ---------------

$DEFAULT_FROM_EMAIL = "admin@domain.tld";
$SERVER_EMAIL = "root@localhost";
$ADMINS = [
    [ "Admin", "admin@domain.tld" ],
];
$EMAIL_BACKEND = 'Phlite\Mail\Backends\Smtp';

# Database settings ----------------------

$DATABASES = [
    'default' => [
        'driver' => 'Phlite\Db\Drivers\Mysql_Pdo',
        'connection' => 'mysql:host=host,port=port;dbname=schema',
        'username' => 'username',
        'password' => 'password',
        'options' => [
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        ],
    ],
];

# Applications --------------------------

$APPLICATIONS = [
    'short' => 'Project\Application1',
];
$TEMPLATE_DIRS = [ 'Templates', ];
$TEMPLATE_CONTEXT_PROCESSORS = [
    'Phlite\Auth\TemplateContext',
    'Phlite\Messages\TemplateContext',
];
$TEMPLATE_LOADERS = [
    'Phlite\Template\Loaders\Filesystem',
    'Phlite\Template\Loaders\AppDirectory',
];

# Request handling ----------------------

$HANDLER = 'Phlite\Request\Handlers\ApacheHandler';
$MIDDLEWARE_CLASSES = [
    'Phlite\Request\Middleware\DbMiddleware',
    'Phlite\Request\Middleware\SessionMiddleware',
    'Phlite\Messages\MessageMiddleware',
];

# Other stuff ---------------------------

$SECRET_KEY = '{{ random_string(64) }}';
