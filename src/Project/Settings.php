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
        $returned = (include $filename);
        if (is_array($returned)) {
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
