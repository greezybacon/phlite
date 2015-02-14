<?php

namespace Phlite\Project;

use Phlite\Util;

class Settings extends Util\ArrayObject {

    function __construct($filename=false) {
        parent::__construct();
        if ($filename)
            $this->loadFile($filename);
    }

    function loadFile($filename) {
        // Load current settings into scope
        extract($this->asArray());
        
        $scope = (include $filename);
        if (is_array($scope)) {
            return $this->update($scope);
        }

        $scope = get_defined_vars();
        $locals = array('returned', 'filename', 'this', 'scope');
        foreach ($locals as $k) {
            unset($scope[$k]);
        }
        
        $this->update($scope);
    }
}
