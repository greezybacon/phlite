<?php

namespace Phlite\Apps\Logging;

use Phlite\Project;

class Application extends Project\Application {
    function getMiddleware() {
        return [__NAMESPACE__ . '\Middleware'];
    }
}