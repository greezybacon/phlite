<?php

namespace Phlite\Mail\Parse\Tnef;

/**
 * References:
 * http://msdn.microsoft.com/en-us/library/ee179447(v=exchg.80).aspx
 */
class TnefAttribute {
    // Encapsulated Attributes
    const AlternateRecipientAllowed = 0x0002;
    const AutoForwarded = 0x0005;
    const Importance = 0x0017;
    const MessageClass = 0x001a;
    const OriginatorDeliveryReportRequested = 0x0023;
    const Priority =  0x0026;
    const ReadReceiptRequested = 0x0029;
    const Sensitivity = 0x0036;
    const ClientSubmitTime = 0x0039;
    const ReceivedByEntryId = 0x003f;
    const ReceivedByName = 0x0040;
    const ReceivedRepresentingEntryId = 0x0043;
    const RecevedRepresentingName = 0x0044;
    const MessageSubmissionId = 0x0047;
    const ReceivedBySearchKey = 0x0051;
    const ReceivedRepresentingSearchKey = 0x0052;
    const MessageToMe = 0x0057;
    const MessgeCcMe = 0x0058;
    const ConversionTopic = 0x0070;
    const ConversationIndex = 0x0071;
    const ReceivedByAddressType = 0x0075;
    const ReceivedByEmailAddress = 0x0076;
    const TnefCorrelationKey = 0x007f;
    const SenderName = 0x0c1a;
    const HasAttachments = 0x0e1b;
    const NormalizedSubject = 0x0e1d;
    const AttachSize = 0x0e20;
    const AttachNumber = 0x0e21;
    const Body = 0x1000;
    const RtfSyncBodyCrc = 0x1006;
    const RtfSyncBodyCount = 0x1007;
    const RtfSyncBodyTag = 0x1008;
    const RtfCompressed = 0x1009;
    const RtfSyncPrefixCount = 0x1010;
    const RtfSyncTrailingCount = 0x1011;
    const BodyHtml = 0x1013;
    const BodyContentId = 0x1014;
    const NativeBody = 0x1016;
    const InternetMessageId = 0x1035;
    const IconIndex = 0x1080;
    const ImapCachedMsgsize = 0x10f0;
    const UrlCompName = 0x10f3;
    const AttributeHidden = 0x10f4;
    const AttributeSystem = 0x10f5;
    const AttributeReadOnly = 0x10f6;
    const CreationTime = 0x3007;
    const LastModificationTime = 0x3008;
    const AttachDataBinary = 0x3701;
    const AttachEncoding = 0x3702;
    const AttachExtension = 0x3703;
    const AttachFilename = 0x3704;
    const AttachLongFilename = 0x3707;
    const AttachPathname = 0x3708;
    const AttachTransportName = 0x370c;
    const AttachMimeTag = 0x370e;    # Mime content-type
    const AttachContentId = 0x3712;
    const AttachmentCharset = 0x371b;
    const InternetCodepage = 0x3fde;
    const MessageLocaleId = 0x3ff1;
    const CreatorName = 0x3ff8;
    const CreatorEntryId = 0x3ff9;
    const LastModifierName = 0x3ffa;
    const LastModifierEntryId = 0x3ffb;
    const MessageCodepage = 0x3ffd;
    const SenderFlags = 0x4019;
    const SentRepresentingFlags = 0x401a;
    const ReceivedByFlags = 0x401b;
    const ReceivedRepresentingFlags = 0x401c;
    const SenderSimpleDisplayName = 0x4030;
    const SentRepresentingSimpleDisplayName = 0x4031;
    # NOTE: The M$ specification gives ambiguous values for this property
    const ReceivedRepresentingSimpleDisplayName = 0x4034;
    const CreatorSimpleDisplayName = 0x4038;
    const LastModifierSimpleDisplayName = 0x4039;
    const ContentFilterSpamConfidenceLevel = 0x4076;
    const MessageEditorFormat = 0x5909;

    static function getName($code) {
        static $prop_codes = false;
        if (!$prop_codes) {
            $R = new ReflectionClass(get_class());
            $prop_codes = array_flip($R->getConstants());
        }
        return $prop_codes[$code];
    }
}
