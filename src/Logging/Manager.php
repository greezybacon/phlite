<?php

namespace Phlite\Logging;

use Phlite\Logging\PlaceHolder;
use Phlite\Logging\Logger;

/**
 * There is [under normal circumstances] just one Manager instance, which
 * holds the hierarchy of loggers
 */
class Manager {
    static $loggerClass = null;

    function __construct($rootnode) {
        $this->root = $rootnode;
        $this->disable = 0;
        $this->emittedNoHandlerWarning = 0;
        $this->loggerDict = array();
    }

    /**
     * Get a logger with the specified name (channel name), creating it if
     * it doesn't yet exist. This name is a dot-separated hierarchical name,
     * sucah as "a", "a.b.", "a.b.c" or similar.
     *
     * If a PlaceHolder existed for the specified name [i.e. the logger
     * didn't exist but a child of it did], replace it with the created
     * logger and fix up the parent/child references which pointed to the
     * placeholder to now point to the logger.
     */
    function getLogger($name) {
        $rv = null;
        if (!is_string($name))
            throw new InvalidArgumentException(
                'Logger names must be a string');
        if (isset($this->loggerDict[$name])) {
            $rv = $this->loggerDict[$name];
            if ($rv instanceof PlaceHolder) {
                $ph = $rv;
                $rv = new static::$loggerClass($name);
                $rv->manager = $this;
                $this->loggerDict[$name] = $rv;
                $this->_fixupChildren($ph, $rv);
                $this->_fixupParents($rv);
            }
        }
        else {
            $rv = new static::$loggerClass($name);
            $this->loggerDict[$name] = $rv;
            $this->_fixupParents($rv);
        }
        return $rv;
    }

    function setLoggerClass($class) {
        if ($class != 'Logger') {
            if (!is_subclass_of($class, 'Phlite\Logging\Logger')) {
                throw new InvalidArgumentException(
                    'logger not derived from Phlite\Logging\Logger');
            }
        }
        static::$loggerClass = $class;
    }

    static function getLoggerClass() {
        return static::$loggerClass;
    }

    /**
     * Ensure that there are either loggers or placeholders all the way from
     * the specified logger to the root of the logger hierarchy.
     */
    function _fixupParents($alogger) {
        $name = $alogger->name;
        $i = strrpos($name, ".");
        $rv = null;
        while ($i > 0 and !$rv) {
            $substr = substr($name, 0, $i);
            if (!isset($this->loggerDict[$substr])) {
                $this->loggerDict[$substr] = new PlaceHolder($alogger);
            }
            else {
                $obj = $this->loggerDict[$substr];
                if ($obj instanceof Logger) {
                    $rv = $obj;
                }
                else {
                    assert($obj instanceof PlaceHolder);
                    $obj->append($alogger);
                }
            }
            $i = strrpos(substr($substr, 0, -$i-1), '.');
        }
        if (!$rv) {
            $rv = $this->root;
        }
        $alogger->parent = $rv;
    }

    /**
     * Ensure that children on the placeholder ph are connected to the
     * specified logger.
     */
    function _fixupChildren($ph, $alogger) {
        $name = $alogger->name;
        $namelen = strlen($name);
        foreach (array_keys($ph->loggerMap) as $c) {
            if (substr($c->parent->name, 0, $namelen) != $name) {
                $alogger->parent = $c->parent;
                $c->parent = $alogger;
            }
        }
    }
}
