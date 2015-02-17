<?php

namespace Phlite\Logging;

use Phlite\Logging\Logger;
use Phlite\Logging\Filterer;

/**
 * Handler instances dispatch logging events to specific destinations.
 *
 * The base handler class. Acts as a placeholder which defines the Handler
 * interface. Handlers can optionally use Formatter instances to format
 * records as desired. By default, no formatter is specified; in this case,
 * the 'raw' message as determined by record.message is logged.
 */
abstract class Handler extends Filterer {

    static $_handlers;

    /**
     * Initializes the instance - basically setting the formatter to None
     * and the filter list to empty.
     */
    function __construct($level=Logger::NOTSET) {
        parent::__construct();
        $this->_name = null;
        $this->level = Logger::_checkLevel($level);
        $this->formatter = null;
        //self::_addHandlerRef($this);
    }

    function __get($prop) {
        if ($prop == 'name') {
            return $this->_name;
        }
    }

    function __set($prop, $val) {
        if ($prop == 'name') {
            unset(self::$_handlers[$this->_name]);
            $this->_name = $val;
            if ($val) {
                self::$_handlers[$val] = $this;
            }
        }
        else {
            $this->{$prop} = $val;
        }
    }

    function setLevel($level) {
        $this->level = Logger::_checkLevel($level);
    }

    function format($record) {
        if ($this->formatter) {
            $fmt = $this->formatter;
        }
        else {
            $fmt = self::$defaultFormatter;
        }
        return $fmt->format($record);
    }

    /**
     * Do whatever it takes to actually log the specified logging record.
     *
     * This version is intended to be implemented by subclasses and so
     * raises a NotImplementedError.
     */
    abstract function emit($record);

    /**
     * Conditionally emit the specified logging record.
     *
     * Emission depends on filters which may have been added to the handler.
     * Wrap the actual emission of the record with acquisition/release of
     * the I/O thread lock. Returns whether the filter passed the record for
     * emission.
     */
    function handle($record) {
        $rv = $this->filter($record);
        if ($rv) {
            $this->emit($record);
        }
        return $rv;
    }

    function setFormatter($fmt) {
        $this->formatter = $fmt;
    }

    /**
     * Ensure all logging output has been flushed.
     *
     * This version does nothing and is intended to be implemented by
     * subclasses.
     */
    function flush() {
    }

    function close() {
        unset(self::$_handlers[$this->_name]);
    }

    /*
     * Handle errors which occur during an emit() call.
     *
     * This method should be called from handlers when an exception is
     * encountered during an emit() call. If raiseExceptions is false,
     * exceptions get silently ignored. This is what is mostly wanted
     * for a logging system - most users will not care about errors in
     * the logging system, they are more interested in application errors.
     * You could, however, replace this with a custom handler if you wish.
     * The record which was being processed is passed in to this method.
     */
    function handleError($record, $ex) {
        $stderr = fopen('php://stderr', 'w');
        fwrite($stderr, $ex->getTraceAsString());
        fwrite($stderr, sprintf(
            "Logged from file %s, line %s\n",
            $record->filename, $record->lineno));
    }
}
