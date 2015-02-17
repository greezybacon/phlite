<?php

namespace Phlite\Logging;

/**
 * An adapter for loggers which makes it easier to specify contextual
 * information in logging output
 */
class LoggerAdapter {
    /**
     * Initialize the adapter with a logger and a dict-like object which
     * provides contextual information. This constructor signature allows
     * easy stacking of LoggerAdapters, if so desired.
     *
     * You can effectively pass keyword arguments as shown in the
     * following example:
     *
     * adapter = LoggerAdapter(someLogger, dict(p1=v1, p2="v2"))
     */
    function __construct($logger, $extra=array()) {
        $this->logger = $logger;
        $this->extra = $extra;
    }

    /**
     * Process the logging message and context passed in to a logging call
     * to insert contextual information. You can either manipulate the
     * message itself, the keyword args or both. Return the message and
     * context modified (or not) to suit your needs.
     *
     * Normally, you'll only need to override this one method in a
     * LoggerAdapter subclass for your specific needs.
     */
    function process($msg, $context) {
        $context['extra'] = $this->extra;
        return array($msg, $context);
    }

    /**
     * Delegate a debug call to the underlying logger, after adding
     * contextual information from this adapter instance.
     */
    function debug($msg, $context=array()) {
        list($msg, $context) = $this->process($msg, $context);
        $this->logger->debug($msg, $context);
    }

    function info($msg, $context=array()) {
        list($msg, $context) = $this->process($msg, $context);
        $this->logger->info($msg, $context);
    }

    function error($msg, $context=array()) {
        list($msg, $context) = $this->process($msg, $context);
        $this->logger->error($msg, $context);
    }

    function exception($msg, $exception, $context=array()) {
        list($msg, $context) = $this->process($msg, $context);
        $this->logger->exception($msg, $exception, $context);
    }

    function log($level, $msg, $context=array()) {
        list($msg, $context) = $this->process($msg, $context);
        $this->logger->log($level, $msg, $context);
    }

    function isEnabledFor($level) {
        return $this->logger->isEnabledFor($level);
    }
}
