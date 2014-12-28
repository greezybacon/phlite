<?php

namespace Phlite\Apps\StaticFiles;

use Phlite\Template;

class TemplateContext implements Template\TemplateContextProcessor {
    
    function getContext() {
        return ['STATIC_DIR' => '/Static'];
    }
}