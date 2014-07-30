<?php

namespace Phlite\Messages\Storage;

use Phlite\Messages\Storage\StorageBackend;
use Phlite\Messages\Messages;

abstract class BaseStorage implements StorageBackend {

    var $level = Messages::NOTSET;

    function __construct($request) {
        $this->queued = array();
        $this->used = false;
        $this->added_new = false;
    }

    function isEnabledFor($level) {
        Messages::checkLevel($level);
        return $level >= $this->getLevel();
    }

    function setLevel($level) {
        Messages::checkLevel($level);
        $this->level = $level;
    }

    function getLevel() {
        return $this->level;
    }

    function load() {
        static $messages = false;

        if (!$messages) {
            $messages = $this->get();
            $messages = $messages ?: array();
        }
        return $messages;
    }

    function getIterator() {
        $this->used = true;
        $messages = $this->load();
        if ($this->queued) {
            $messages = array_merge($messages, $this->queued);
            $this->queued = array();
        }
        return new \ArrayIterator($messages);
    }

    function update($response) {
        if ($this->used) {
            return $this->store($this->queued, $response);
        }
        else {
            $messages = array_merge($this->load(), $this->queued);
            return $this->store($messages, $response);
        }
    }

    function add($level, $message) {
        if (!$message)
            return;
        elseif (!$this->isEnabledFor($level))
            return;

        $this->added_new = true;
        $this->queued[] = $message;
    }

    abstract function get();
    abstract function store($message, $response);
}
