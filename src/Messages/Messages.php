<?php

namespace Phlite\Messages;

class Messages {

    const ERROR = 50;
    const WARNING = 40;
    const WARN = self::WARNING;
    const SUCCESS = 30;
    const INFO = 20;
    const DEBUG = 10;
    const NOTSET = 0;

    static $_levelNames = array(
        self::ERROR => 'ERROR',
        self::WARNING => 'WARNING',
        self::SUCCESS => 'SUCCESS',
        self::INFO => 'INFO',
        self::DEBUG => 'DEBUG',
        self::NOTSET => 'NOTSET',
        'ERROR' => self::ERROR,
        'WARN' => self::WARNING,
        'WARNING' => self::WARNING,
        'SUCCESS' => self::SUCCESS,
        'INFO' => self::INFO,
        'DEBUG' => self::DEBUG,
        'NOTSET' => self::NOTSET,
    );

    static $messageClass = 'SimpleMessage';

    static function debug($request, $message) {
        static::addMessage($request, self::DEBUG, $message);
    }
    static function info($request, $message) {
        static::addMessage($request, self::INFO, $message);
    }
    static function success($request, $message) {
        static::addMessage($request, self::SUCCESS, $message);
    }
    static function warning($request, $message) {
        static::addMessage($request, self::WARNING, $message);
    }
    static function error($request, $message) {
        static::addMessage($request, self::ERROR, $message);
    }

    static function addMessage($request, $level, $message) {
        if (!$request instanceof HttpRequest) {
            throw new InvalidArgumentException(
                'Request must be an HttpRequest instance');
        }
        elseif (!isset($request->messages)) {
            throw new RuntimeException(
                __NAMESPACE__.'\MessagesMiddleware must be installed '
               .'in order to add messages');
        }

        $msg = new static::$messageClass($level, $message);
        $bk = $this->getMessages($request);
        $bk->add($level, $msg);
    }

    static function getMessages($request) {
        return $request->messages;
    }

    static function setMessageClass($class) {
        if (!is_subclass_of($class, 'Message'))
            throw new InvalidArgumentException('Class must extend Message');
        self::$messageClass = $class;
    }

    static function checkLevel($level) {
        if (is_int($level)) {
            $rv = $level;
        }
        elseif ((string) $level == $level) {
            if (!isset(static::$_levelNames[$level]))
                throw new InvalidArgumentException(
                    sprintf('Unknown level: %s', $level));
            $rv = static::$_levelNames[$level];
        }
        else {
            throw new InvalidArgumentException(
                sprintf('Level not an integer or a valid string: %s',
                    $level));
        }
        return $rv;
    }
}
