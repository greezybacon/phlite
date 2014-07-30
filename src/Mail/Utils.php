<?php

namespace Phlite\Mail;


function mail_admins($subject, $body, $attachments=array(),
        $options=array()) {

    $settings = Phlite\Project::getGlobalSettings();
    $recipients = $settings['ADMINS'];

    // TODO: Massage the recipients list format
    namespace\Mailer::send($recipients, $subject, $body, $attachments,
        $options);
}
