<?php

namespace Phlite\Mail;

use Phlite\Project;

function mail_admins($subject, $body, $attachments=array(),
        $options=array()) {

    $settings = Project::currentProject()->getSettings();
    $recipients = $settings['ADMINS'];

    // TODO: Massage the recipients list format
    Mailer::send($recipients, $subject, $body, $attachments,
        $options);
}
