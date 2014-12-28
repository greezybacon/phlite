<?php

namespace Phlite\Apps\StaticFiles;

use Phlite\Project;

class Application extends Project\Application {
    
    function getTemplateContexts() {
        return [ __NAMESPACE__ . '\TemplateContext' ];
    }
}