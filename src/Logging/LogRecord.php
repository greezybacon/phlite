<?php

namespace Phlite\Logging;

use Phlite\Logging\Logger;

/**
 * A LogRecord instance represents an event being logged.
 *
 * LogRecord instances are created every time something is logged. They
 * contain all the information pertinent to the event being logged. The
 * main information passed in is in msg and args, which are combined
 * using str(msg) % args to create the message field of the record. The
 * record also includes information such as when the record was created,
 * the source line where the logging call was made, and any exception
 * information to be logged.
 */
class LogRecord {

    static $_startTime;

    function __construct($name, $level, $pathname, $lineno, $msg,
            $args, $exc_info, $func=null) {

        $ct = microtime(true);
        $this->name = $name;
        $this->msg = $msg;

        $this->args = $args;
        $this->levelname = Logger::getLevelName($level);
        $this->levelno = $level;
        $this->pathname = $pathname;
        $this->filename = basename($pathname);
        $this->exc_info = $exc_info;
        $this->exc_text = null;
        $this->lineno = $lineno;
        $this->funcName = $func;
        $this->created = (int) $ct;
        $this->msecs = ($ct - $this->created) * 1000;
        $this->relativeCreated = ($this->created - static::$_startTime)
            * 1000;
    }

    function __toString() {
        return sprintf('<LogRecord: %s, %s, %s, %s, "%s">',
            $this->name, $this->levelno, $this->pathname, $this->lineno,
            $this->msg);
    }

    function getMessage() {
        $msg = $this->msg;
        foreach ($this->args as $k=>$v) {
            $msg = str_replace("{{$k}}", $v, $msg);
        }
        return $msg;
    }

    /**
     * Make a LogRecord whose attributes are defined by the specified dictionary,
     * This function is useful for converting a logging event received over
     * a socket connection (which is sent as a dictionary) into a LogRecord
     * instance.
     */
    static function makeRecord($dict) {
        $rv = new LogRecord(null, null, '', 0, '', array(), null, null);
        foreach ($dict as $k=>&$v) {
            $rv->{$k} = $v;
        } 
        return $rv;
    }
}

LogRecord::$_startTime = $_SERVER['REQUEST_TIME_FLOAT'];