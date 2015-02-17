<?php

namespace Phlite\Logging;
/**
 * Formatter instances are used to convert a LogRecord to text.
 *
 * Formatters need to know how a LogRecord is constructed. They are
 * responsible for converting a LogRecord to (usually) a string which can
 * be interpreted by either a human or an external system. The base Formatter
 * allows a formatting string to be specified. If none is supplied, the
 * default value of "%s(message)\\n" is used.
 *
 * The Formatter can be initialized with a format string which makes use of
 * knowledge of the LogRecord attributes - e.g. the default value mentioned
 * above makes use of the fact that the user's message and arguments are pre-
 * formatted into a LogRecord's message attribute. Currently, the useful
 * attributes in a LogRecord are described by:
 *
 * {name}              Name of the logger (logging channel)
 * {levelno}           Numeric logging level for the message (DEBUG, INFO,
 *                     WARNING, ERROR, CRITICAL)
 * {levelname}         Text logging level for the message ("DEBUG", "INFO",
 *                     "WARNING", "ERROR", "CRITICAL")
 * {pathname}          Full pathname of the source file where the logging
 *                     call was issued (if available)
 * {filename}          Filename portion of pathname
 * {module}            Module (name portion of filename)
 * {lineno}            Source line number where the logging call was issued
 *                     (if available)
 * {funcName}          Function name
 * {created}           Time when the LogRecord was created (time.time()
 *                     return value)
 * {asctime}           Textual time when the LogRecord was created
 * {msecs}             Millisecond portion of the creation time
 * {relativeCreated}   Time in milliseconds when the LogRecord was created,
 *                     relative to the time the logging module was loaded
 *                     (typically at application startup time)
 * {thread}            Thread ID (if available)
 * {threadName}        Thread name (if available)
 * {process}           Process ID (if available)
 * {message}           The result of record.getMessage(), computed just as
 *                     the record is emitted
 */
class Formatter {
    static $converter = 'date';

    function __construct($fmt=null, $datefmt=null) {
        if ($fmt)
            $this->_fmt = $fmt;
        else
            $this->_fmt = '{message}';
        $this->datefmt = $datefmt;
    }

    /**
     * Return the creation time of the specified LogRecord as formatted text.
     *
     * This method should be called from format() by a formatter which
     * wants to make use of a formatted time. This method can be overridden
     * in formatters to provide for any specific requirement, but the
     * basic behaviour is as follows: if datefmt (a string) is specified,
     * it is used with time.strftime() to format the creation time of the
     * record. Otherwise, the ISO8601 format is used. The resulting
     * string is returned. This function uses a user-configurable function
     * to convert the creation time to a tuple. By default, time.localtime()
     * is used; to change this for a particular formatter instance, set the
     * 'converter' attribute to a function with the same signature as
     * time.localtime() or time.gmtime(). To change it for all formatters,
     * for example if you want all logging times to be shown in GMT,
     * set the 'converter' attribute in the Formatter class.
     */
    function formatTime($record, $datefmt=null) {
        $ct = $record->created;
        // TODO: Use static::$converter to convert to localtime
        if ($datefmt) {
            $s = strftime($datefmt, $ct);
        }
        else {
            $t = strftime('%Y-%m-%d %H:%M:%S', $ct);
            $s = sprintf('%s,%03d', $t, $record->msecs);
        }
        return $s;
    }

    function formatException($ei) {
        return rtrim($ei->getTraceAsString(), "\n");
    }

    /**
     * Check if the format uses the creation time of the record.
     */
    function usesTime() {
        return strpos($this->_fmt, '{asctime}') !== false;
    }

    /**
     * Format the specified record as text.
     *
     * The record's attribute dictionary is used as the operand to a
     * string formatting operation which yields the returned string.
     * Before formatting the dictionary, a couple of preparatory steps
     * are carried out. The message attribute of the record is computed
     * using LogRecord.getMessage(). If the formatting string uses the
     * time (as determined by a call to usesTime(), formatTime() is
     * called to format the event time. If there is exception information,
     * it is formatted using formatException() and appended to the message.
     */
    function format($record) {
        $record->message = $record->getMessage();
        if ($this->usesTime()) {
            $record->asctime = $this->formatTime($record, $this->datefmt);
        }
        $s = $this->_template($this->_fmt, get_object_vars($record));
        if ($record->exc_info) {
            # Cache the traceback text to avoid converting it multiple times
            # (it's constant anyway)
            if (!$record->exc_text) {
                $record->exc_text = $this->formatException($record->exc_info);
            }
        }
        if ($record->exc_text) {
            if (substr($s, -1) != "\n")
                $s .= "\n";
            $s .= $record->exc_text;
        }
        return $s;
    }

    function _template($template, $context) {
        return preg_replace_callback('/{([^}]+)}/',
            function ($token) use ($context) {
                return @$context[$token[1]] ?: $token[0];
            }, $template
        );
    }
}
