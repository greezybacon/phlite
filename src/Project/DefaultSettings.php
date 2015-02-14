<?php

namespace Phlite\Project;

class DefaultSettings extends Settings {

    function __construct() {
        call_user_func_array(array('parent', '__construct'), func_get_args());
        $this->loadFile(__DIR__ . '/Data/BaseSettings.php');
    }

}