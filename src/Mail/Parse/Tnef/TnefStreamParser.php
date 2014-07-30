<?php

namespace Phlite\Mail\Parse\Tnef;

class TnefStreamParser {
    const LVL_MESSAGE = 0x01;
    const LVL_ATTACHMENT = 0x02;

    const attTnefVersion = 0x89006;
    const attAttachData = 0x6800f;
    const attAttachTransportFilename = 0x18010;
    const attAttachRendData = 0x69002;
    const attAttachment = 0x69005;
    const attMsgProps = 0x69003;
    const attRecipTable = 0x69004;
    const attOemCodepage = 0x69007;

    // Message-level attributes
    const idMessageClass = 0x78008;
    const idSenderEntryId = 0x8000;         # From
    const idSubject = 0x18004;              # Subject
    const idClientSubmitTime = 0x38004;
    const idMessageDeliveryTime = 0x38005;
    const idMessageStatus = 0x68007;
    const idMessageID = 0x18009;            # Message-Id
    const idConversationID = 0x1800b;
    const idBody = 0x2800c;                 # Body
    const idImportance = 0x4800d;           # Priority
    const idLastModificationTime = 0x38020;
    const idOriginalMessageClass = 0x70600;
    const idReceivedRepresentingEmailAddress = 0x60000;
    const idSentRepresentingEmailAddress = 0x60001;
    const idStartDate = 0x030006;
    const idEndDate = 0x30007;
    const idOwnerAppointmentId = 0x50008;
    const idResponseRequested = 0x40009;

    function __construct($stream) {
        $this->stream = new TnefStreamReader($stream);
    }

    function getMessage() {
        $msg = new TnefMessage();
        foreach ($this->stream as $type=>$info) {
            switch($type) {
            case self::attTnefVersion:
                // Ignored (for now)
                break;

            case self::attOemCodepage:
                $cp = unpack("Vpri/Vsec", $info['data']);
                $msg->_set('OemCodepage', $cp['pri']);
                break;

            // Message level attributes
            case self::idMessageClass:
                $msg->_set('MessageClass', $info['data']);
                break;
            case self::idMessageID:
                $msg->_set('MessageId', $info['data']);
                break;

            case self::attMsgProps:
                // Message properties (includig body)
                $msg->_setProperties(
                    new TnefAttributeStreamReader($info['data']));
                break;

            // Attachments
            case self::attAttachRendData:
                // Marks the start of an attachment
                $attach = $msg->pushAttachment();
                //$attach->_setRenderingData();
                break;
            case self::attAttachment:
                $attach->_setProperties(
                    new TnefAttributeStreamReader($info['data']));
                break;
            case self::attAttachTransportFilename:
                $attach->_setFilename(rtrim($info['data'], "\x00"));
                break;
            case self::attAttachData:
                $attach->_setData($info['data']);
                break;
            }
        }
        return $msg;
    }
}
