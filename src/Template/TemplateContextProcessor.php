<?php

namespace Phlite\Template;

interface TemplateContextProcessor {
    function getContext($request);
}