<?php

namespace Phlite\Mail\Parse\Tnef;

use Phlite\Mail\Parse\AbstractTnefObject;

class TnefAttachment extends AbstractTnefObject {
    function _setFilename($data) {
        $this->TransportFilename = $data;
    }

    function _setData($data) {
        $this->Data = $data;
    }

    function getData() {
        if (isset($this->Data))
            return $this->Data;
        elseif (isset($this->AttachDataBinary))
            return $this->AttachDataBinary;
    }

    function _setRenderingData($data) {
        // Pass
    }

    function getType() {
        return $this->AttachMimeTag;
    }

    function getName() {
        if (isset($this->AttachLongFilename))
            return basename($this->AttachLongFilename);
        elseif (isset($this->AttachFilename))
            return $this->AttachFilename;
        elseif (isset($this->AttachTransportName))
            return $this->AttachTransportName;
        else
            return $this->TransportFilename;
    }
}
