<?php

namespace Phlite\Dispatch\Handlers;

use Phlite\Dispatch;
use Phlite\Logging;
use Phlite\Project;

class PhpDevServerHandler extends Dispatch\BaseHandler {
    
    function __construct(Project $project) {
        parent::__construct($project);

        // Log requests to stderr
        $l = Logging\Log::getLogger('phlite.request');
        $h = new Logging\Handler\StreamHandler();
        $h->setFormatter(new ShortExceptionFormatter(
            // Make this configurable ?
            '{ip} — [{asctime}] {name} [{status}] — {verb} {path} {message} ({levelname})',
            '%a %b %d %Y %H:%M:%S',
            $project
        ));
        $l->setLevel(Logging\Logger::INFO);
        $l->addHandler($h);
    }
    
    function getPathInfo() {
        if (isset($_SERVER['PATH_INFO']))
            return $_SERVER['PATH_INFO'];
        
        $path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
        // Strip leading slash
        return ltrim($path, '/');
    }
}

class ShortExceptionFormatter extends Logging\Formatter {
    var $root;
    
    function __construct($fmt, $datefmt, $project) {
        parent::__construct($fmt, $datefmt);
        $this->root = $project->getFilesystemRoot();
    }
    
    function formatException($ex) {
        $text = parent::formatException($ex);
        return str_replace($this->root, '(project)', $text);
    }
}