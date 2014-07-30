<?php

namespace Phlite\Logging;

/**
 * PlaceHolder instaces are used in the Manager logger hierarchy to take the
 * place of nodes for which no loggers have been defined. This class is
 * intended for internal use only and not as part of the public API
 */
class PlaceHolder {

    function __construct($alogger) {
        $this->loggerMap = array( spl_object_hash($alogger) => null );
    }

    function append($alogger) {
        $key = spl_object_hash($alogger);
        if (!isset($this->loggerMap[$key])) {
            $this->loggerMap[$key] = $alogger;
        }
    }
}
