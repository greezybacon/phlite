<?php

namespace Phlite\Project;

use Phlite\Util\Dict;

class Settings extends Dict {

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
            return $this->update($returned);
        }

        $scope = get_defined_vars();
        $locals = array('returned', 'filename', 'this');
        foreach ($locals as $k) {
            unset($scope[$k]);
        }
        return $this->update($scope);
    }
}
