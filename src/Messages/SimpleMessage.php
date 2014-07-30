<?php

namespace Phlite\Messages;

use Phlite\Messages\Message;

class SimpleMessage implements Message {

    var $tags;
    var $level;
    var $msg;

    function __construct($level, $message, $extra_tags=array()) {
        $this->level = $level;
        $this->msg = $message;
        $this->tags = $extra_tags;
    }

    function getTags() {
        $tags = array_merge(
            array(Messages::getLevelTag($this->level)),
            $this->tags);
        return implode(' ', $tags);
    }

    function getLevel() {
        return Messages::getLevelName($this->level);
    }

    function __toString() {
        return $this->msg;
    }
}
