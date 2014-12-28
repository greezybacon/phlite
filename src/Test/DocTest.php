<?php

namespace Phlite\Test;

use Phlite\Cli\Interact;
use Phlite\Test\TestModule;

/**
 * This represents a single test of the docstrings listed in class
 * documentation. Use the Inspector to find and create instances of this
 * class for testing.
 */
class DocTest {
    
    protected static $user_flags = array();
    
    protected $expression;
    protected $result;
    protected $flags = 0;
    
    const ELIPSIS =                 0x0001;
    const NORMALIZE_WHITESPACE =    0x0002;
    const SKIP =                    0x0004;
    protected static $FLAG_HIGHEST = self::SKIP;
    
    function __construct($expression, $result, $flags=0) {
        $this->expression = $expression;
        $this->result = $result;
        $this->flags = $this->scanFlags($expression, $flags);
    }
    
    function checkOutput($output) {
        $expect = implode("\n", $this->result);
        $checker = new OutputChecker();
        $base = $checker->checkOutput($expect, $output, $this->flags);
        foreach ($this->handlers as $name=>$handler) {
            if (is_subclass_of($handler, OutputChecker)) {
                $C = new $handler();
                $base &= $C->checkOutput($expect, $output, $this->flags);
            }
        }
        return $base;
    }
    
    function scanFlags(array $lines, $flags=0) {
        foreach ($lines as $L) {
            $matches = array();
            if (preg_match('/doctest:\s+((?:[-+]\w+\s*)+)/', $L, $matches)) {
                foreach (preg_split('/\s+/', $matches[1]) as $F) {
                    $on = $F[0];
                    $name = substr($F, 1);
                    if (isset(self::$user_flags[$name])) {
                        list($value, $handler) = self::$user_flags[$name];
                        $this->handlers[$name] = $handler;
                    }
                    elseif (!($value = constant(sprintf('%s::%s', get_called_class(), $name)))) {
                        // TODO: Emit no-such-flag warning
                        throw new Exception\NoSuchFlag(
                            $name . ': No such doc test flag registered');
                    }
                    if ($F[0] == '+')
                        $flags |= $value;
                    else
                        $flags &= ~$value;
                }
            }
        }
        return $flags;
    }
    
    static function checkFile($filename) {
        $runner = new DocTestRunner();
        $finder = new Inspector();
        $tests = $finder->findTests(file_get_contents($filename));
        return $runner->runAll($tests);
    }
    
    /**
     * register_directive
     *
     * Register a directive to infleuence the interpretation of the doctest
     * expression and result.
     */
    static function registerFlag($name, $handler) {
        $next = self::$FLAG_HIGHEST <<= 1;
        self::$user_flags[$name] = array($next, $handler);
        return $next;
    }
}