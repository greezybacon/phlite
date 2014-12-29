<?php

namespace Phlite\Mail\Parse\Tnef;

class TnefMessage extends AbstractTnefObject {
    var $attachments = array();

    function pushAttachment() {
        $new = new TnefAttachment();
        $this->attachments[] = $new;
        return $new;
    }

    function getBody($type='text/html', $encoding=false) {
        // First select the body
        switch ($type) {
        case 'text/html':
            $body = $this->BodyHtml;
            break;
        default:
            return false;
        }

        // Figure out the source encoding (§5.1.2)
        $charset = false;
        if (@$this->OemCodepage)
            $charset = 'cp'.$this->OemCodepage;
        elseif (@$this->InternetCodepage)
            $charset = 'cp'.$this->InternetCodepage;

        // Transcode it
        if ($encoding && $charset)
            $body = Format::encode($body, $charset, $encoding);

        return $body;
    }
}
