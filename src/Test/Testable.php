<?php

namespace Phlite\Test;

abstract class Testable {
    protected $fails = array();
    protected $warnings = array();
    
    var $name = "";

    function __construct() {
        error_reporting(E_ALL & ~E_WARNING);
    }

    function setup() {
        assert_options(ASSERT_CALLBACK, array($this, 'failAssert'));
    }

    function teardown() {
    }

    static function getAllScripts($excludes=true, $root=false) {
        $root = $root ?: get_osticket_root_path();
        $scripts = array();
        foreach (glob_recursive("$root/*.php") as $s) {
            $found = false;
            if ($excludes) {
                foreach (self::$third_party_paths as $p) {
                    if (strpos($s, $p) !== false) {
                        $found = true;
                        break;
                    }
                }
            }
            if (!$found)
                $scripts[] = $s;
        }
        return $scripts;
    }

    function failAssert($script, $line, $message, $description=false) {
        throw new Fail(array(
            'class'=>get_class($this), 'script'=>$script, 'line'=>$line, 
            'message'=>$description ?: $message
        ));
    }
    
    function fail($message, array $info) {
        $base = $info ?: array();
        $base['message'] = $message;
        if (!isset($base['script'])) {
            // TODO: Get the script and line out of a backtrace
        }
    }

    function pass() {
    }

    function warn($message) {
        throw new Warning(array(
            'class'=>get_class($this), 'message'=>$message
        ));
    }

    function assert($expr, $message) {
        if ($expr)
            $this->pass();
        else
            $this->fail('', '', $message);
    }

    function assertEqual($a, $b, $message=false) {
        if (!$message)
            $message = "Assertion: {$a} != {$b}";
        return $this->assert($a == $b, $message);
    }

    function assertNotEqual($a, $b, $message=false) {
        if (!$message)
            $message = "Assertion: {$a} == {$b}";
        return $this->assert($a != $b, $message);
    }

    abstract function run();
}