<?php

namespace Phlite\Project;

class Settings extends ArrayObject {

    private $settings = array(
        'applications' => array(
        ),
        'middleware' => array(
            'Phlite\Request\Middleware\SessionMiddleware',
        ),
        'template_context' => array(
            'Phlite\Template'
        ),
    );

    function __construct() {
        parent::__construct($this->settings);
    }

    function loadFile($filename) {
        $returned = (include $filename);
        if ($returned) {
            return $this->merge($returned);
        }

        $scope = get_defined_vars();
        $locals = array('returned', 'filename', 'this');
        foreach ($locals as $k) {
            unset($scope[$k]);
        }
        return $this->merge($scope);
    }

    function merge($scope) {
        $this->settings = array_merge_recursive(
            $this->settings, $scope);
    }

    function get($key, $default=null) {
        if (isset($this[$key])) {
            $rv = $this[$key];
        }
        elseif (isset($default)) {
            $rv = $default;
        }
        else {
            throw new Exception('Setting not defined');
        }
        return $default;
    }
}
