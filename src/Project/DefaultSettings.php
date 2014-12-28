<?php

namespace Phlite\Project;

class DefaultSettings extends Settings {

    function __construct() {
        $this->load(__DIR__ . '/Data/BaseSettings.php');
        call_user_func_array(array('parent', '__construct'), func_get_args());
    }

}