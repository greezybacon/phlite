<?php

namespace Phlite\Logging;

use Phlite\Logging\Filterer;
use Phlite\Logging\Manager;

/**
 * Instances of the Logger class represent a single logging channel. A
 * "logging channel" indicates an area of an application. Exactly how an
 * "area" is defined is up to the application developer. Since an
 * application can have any number of areas, logging channels are identified
 * by a unique string. Application areas can be nested (e.g. an area
 * of "input processing" might include sub-areas "read CSV files", "read
 * XLS files" and "read Gnumeric files"). To cater for this natural nesting,
 * channel names are organized into a namespace hierarchy where levels are
 * separated by periods, much like the Java or Python package namespace. So
 * in the instance given above, channel names might be "input" for the upper
 * level, and "input.csv", "input.xls" and "input.gnu" for the sub-levels.
 * There is no arbitrary limit to the depth of nesting.
 */
class Logger extends Filterer {

    static $root;
    static $manager;

    const CRITICAL = 50;
    const FATAL = self::CRITICAL;
    const ERROR = 40;
    const WARNING = 30;
    const WARN = self::WARNING;
    const INFO = 20;
    const DEBUG = 10;
    const NOTSET = 0;

    static $_levelNames = array(
        self::CRITICAL => 'CRITICAL',
        self::ERROR => 'ERROR',
        self::WARNING => 'WARNING',
        self::INFO => 'INFO',
        self::DEBUG => 'DEBUG',
        self::NOTSET => 'NOTSET',
        'CRITICAL' => self::CRITICAL,
        'ERROR' => self::ERROR,
        'WARN' => self::WARNING,
        'WARNING' => self::WARNING,
        'INFO' => self::INFO,
        'DEBUG' => self::DEBUG,
        'NOTSET' => self::NOTSET,
    );

    /**
     * Return the textual representation of logging level 'level'.
     *
     * If the level is one of the predefined levels (CRITICAL, ERROR, WARNING,
     * INFO, DEBUG) then you get the corresponding string. If you have
     * associated levels with names using addLevelName then the name you have
     * associated with 'level' is returned.
     *
     * If a numeric value corresponding to one of the defined levels is passed
     * in, the corresponding string representation is returned.
     *
     * Otherwise, the string "Level %s" % level is returned.
     */
    static function getLevelName($level) {
        return static::$_levelNames[$level] ?: sprintf('Level %s', $level);
    }

    static function addLevelName($level, $levelName) {
        static::$_levelNames[$level] = $levelName;
        static::$_levelNames[$levelName] = $level;
    }

    static function _checkLevel($level) {
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

    function __construct($name, $level=self::NOTSET) {
        parent::__construct();
        $this->name = $name;
        $this->level = $this->_checkLevel($level);
        $this->parent = null;
        $this->propogate = 1;
        $this->handlers = array();
        $this->disabled = 0;
    }

    function setLevel($level) {
        $this->level = $this->_checkLevel($level);
    }

    /**
     * Log 'msg % args' with severity 'DEBUG'.
     *
     * To pass exception information, use the keyword argument exc_info with
     * a true value, e.g.
     *
     * logger.debug("Houston, we have a %s", "thorny problem", exc_info=1)
     */
    function debug($msg, $context=array()) {
        if ($this->isEnabledFor(self::DEBUG))
            $this->_log(self::DEBUG, $msg, $context);
    }

    function info($msg, $context=array()) {
        if ($this->isEnabledFor(self::INFO))
            $this->_log(self::INFO, $msg, $context);
    }

    function warning($msg, $context=array()) {
        if ($this->isEnabledFor(self::WARNING))
            $this->_log(self::WARNING, $msg, $context);
    }

    function warn($msg, $context=array()) {
        return $this->warning($msg, $context);
    }

    function error($msg, $context=array()) {
        if ($this->isEnabledFor(self::ERROR))
            $this->_log(self::ERROR, $msg, $context);
    }

    function exception($msg, $exception, $context=array()) {
        $context['exception'] = $exception;
        return $this->error($msg, $context);
    }

    function critical($msg, $context=array()) {
        if ($this->isEnabledFor(self::CRITICAL)) {
            $this->_log(self::CRITICAL, $msg, $context);
        }    
    }

    function fatal($msg, $context=array()) {
        return $this->critical($msg, $context);
    }

    function log($level, $msg, $context=array()) {
        if (!is_int($level)) {
            // TODO: Convert level to an int
        }
        if ($this->isEnabledFor($level))
            $this->_log($level, $msg, $context);
    }

    function findCaller() {
        // TODO: Test if Xdebug is faster than debug_backtrace(),
        //       and also new Exception()->getTrace()
        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        array_shift($bt);
        while ($frame = array_shift($bt)) {
            $filename = $frame['file'];
            if (strcasecmp($filename, __FILE__) === 0)
                continue;
            $rv = array($frame['file'], $frame['line'], $frame['function']);
            break;
        }
        return @$rv;
    }

    function makeRecord($name, $level, $fn, $lno, $msg, $context, $exc_info,
            $func=null, $extra=null) {
        $rv = new LogRecord($name, $level, $fn, $lno, $msg, $context,
            $exc_info, $func);
        if (is_array($extra)) {
            foreach ($extra as $key=>$value) {
                if (in_array($key, array('message','asctime'))
                        or isset($rv->{$key}))
                    throw new InvalidArgumentException(
                        sprintf('Attempt to overwrite %s in LogRecord',
                        $key));
                $rv->{$key} = $value;
            }
        }
        return $rv;
    }

    function _log($level, $msg, $context) {
        $exc_info = @$context['exception']; unset($context['exception']);
        $extra = @$context['extra']; unset($context['extra']);
        if (!isset($exc_info)) {
            list($fn, $lno, $func) = $this->findCaller();
        }
        elseif (is_array($exc_info)) {
            $fn = $exc_info['file'];
            $lno = $exc_info['line'];
            $func = $exc_info['function'];
        }
        elseif ($exc_info instanceof \Exception) {
            $fn = $exc_info->getFile();
            $lno = $exc_info->getLine();
            $T = $exc_info->getTrace();
            $func = $T[0]['function'];
        }
        $record = $this->makeRecord($this->name, $level, $fn, $lno, $msg,
            $context, $exc_info, $func, $extra);
        $this->handle($record);
    }

    function handle($record) {
        if (!$this->disabled && $this->filter($record)) {
            $this->callHandlers($record);
        }
    }

    function addHandler($hdlr) {
        if (!in_array($hdlr, $this->handlers))
            $this->handlers[] = $hdlr;
    }

    function removeHandler($hdlr) {
        if ($k = array_search($this->handlers, $hdlr)) {
            unset($this->handlers[$k]);
        }
    }

    /**
     * Pass a record to all relevant handlers.
     *
     * Loop through all handlers for this logger and its parents in the
     * logger hierarchy. If no handler was found, output a one-off error
     * message to sys.stderr. Stop searching up the hierarchy whenever a
     * logger with the "propagate" attribute set to zero is found - that
     * will be the last logger whose handlers are called.
     */
    function callHandlers($record) {
        $c = $this;
        $found = 0;
        while ($c) {
            foreach ($c->handlers as $hdlr) {
                $found += 1;
                if ($record->levelno >= $hdlr->level) {
                    $hdlr->handle($record);
                }
            }
            if (!$c->propogate) {
                $c = null;      // Break out
            }
            else {
                $c = $c->parent;
            }
        }
        if ($found == 0 and !static::$manager->emittedNoHandlerWarning) {
            $stderr = fopen('php://stderr', 'w');
            fwrite($stderr, "No handlers could be found for logger"
                .sprintf(" \"%s\"\n", $this->name));
            static::$manager->emittedNoHandlerWarning = 1;
        }
    }

    function getEffectiveLevel() {
        $logger = $this;
        while ($logger) {
            if ($logger->level) {
                return $logger->level;
            }
            $logger = $logger->parent;
        }
        return self::NOTSET;
    }

    function isEnabledFor($level) {
        if (static::$manager->disable >= $level)
            return 0;
        return $level >= $this->getEffectiveLevel();
    }

    /**
     * Get a logger which is a descendant to this one.
     *
     * This is a convenience method, such that
     *
     * logging.getLogger('abc').getChild('def.ghi')
     *
     * is the same as
     *
     * logging.getLogger('abc.def.ghi')
     *
     * It's useful, for example, when the parent logger is named using
     * __namespace__ rather than a literal string.
     */
    function getChild($suffix) {
        if (static::$root !== $this) {
            $suffix = $this->name . '.' . $suffix;
        }
        return static::$manager->getLogger($suffix);
    }
}

