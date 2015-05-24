<?php

namespace Phlite\Cli;

use Phlite\Io;

abstract class Module {
    static $registry = array();

    var $options = array();
    var $arguments = array();
    var $prologue = "";
    var $epilog = "";
    var $usage = '$script [options] $args [arguments]';
    var $autohelp = true;
    var $module_name;

    var $stdout;
    var $stderr;

    var $_options;
    var $_args;

    function __construct() {
        $this->options['help'] = array("-h","--help",
            'action'=>'store_true',
            'help'=>"Display this help message");
        foreach ($this->options as &$opt)
            $opt = new Option($opt);
        $this->stdout = new Io\OutputStream('php://output');
        $this->stderr = new Io\OutputStream('php://stderr');
    }

    function showHelp() {
        if ($this->prologue)
            echo $this->prologue . "\n\n";

        global $argv;
        $manager = @$argv[0];

        echo "Usage:\n";
        echo "    " . str_replace(
                array('$script', '$args'),
                array($manager ." ". $this->module_name, implode(' ', array_keys($this->arguments))),
            $this->usage) . "\n";

        ksort($this->options);
        if ($this->options) {
            echo "\nOptions:\n";
            foreach ($this->options as $name=>$opt)
                echo $opt->toString() . "\n";
        }

        if ($this->arguments) {
            echo "\nArguments:\n";
            foreach ($this->arguments as $name=>$help)
                $extra = '';
                if (is_array($help)) {
                    if (isset($help['options']) && is_array($help['options'])) {
                        foreach($help['options'] as $op=>$desc)
                            $extra .= wordwrap(
                                "\n        $op - $desc", 76, "\n            ");
                    }
                    $help = $help['help'];
                }
                echo $name . "\n    " . wordwrap(
                    preg_replace('/\s+/', ' ', $help), 76, "\n    ")
                        .$extra."\n";
        }

        if ($this->epilog) {
            echo "\n\n";
            $epilog = preg_replace('/\s+/', ' ', $this->epilog);
            echo wordwrap($epilog, 76, "\n");
        }

        echo "\n";
    }

    function getOption($name, $default=false) {
        $this->parseOptions();
        if (isset($this->_options[$name]))
            return $this->_options[$name];
        elseif (isset($this->options[$name]) && $this->options[$name]->default)
            return $this->options[$name]->default;
        else
            return $default;
    }

    function getArgument($name, $default=false) {
        $this->parseOptions();
        if (isset($this->_args[$name]))
            return $this->_args[$name];
        return $default;
    }

    function parseOptions() {
        if (is_array($this->_options))
            return;

        global $argv;
        list($this->_options, $this->_args) =
            $this->parseArgs(array_slice($argv, 1));

        foreach (array_keys($this->arguments) as $idx=>$name)
            if (!isset($this->_args[$idx]))
                $this->optionError($name . " is a required argument");
            elseif (is_array($this->arguments[$name])
                    && isset($this->arguments[$name]['options'])
                    && !isset($this->arguments[$name]['options'][$this->_args[$idx]]))
                $this->optionError($name . " does not support such a value");
            else
                $this->_args[$name] = &$this->_args[$idx];

        foreach ($this->options as $name=>$opt)
            if (!isset($this->_options[$name]))
                $this->_options[$name] = $opt->default;

        if ($this->autohelp && $this->getOption('help')) {
            $this->showHelp();
            die();
        }
    }

    function optionError($error) {
        echo "Error: " . $error . "\n\n";
        $this->showHelp();
        die();
    }

    function _run($module_name=false) {
        $this->module_name = $module_name;
        $this->parseOptions();
        return $this->run($this->_args, $this->_options);
    }

    abstract function run($args, $options);
    
    function getName() {
        $class = get_class($this);
        $parts = explode('\\', $class);
        return strtolower(array_pop($parts));
    }

    function fail($message) {
        $TI = $this->stderr->getTermInfo();
        $this->stderr->write($TI->template(
            "{setaf:WHITE}!!! {setaf:RED}$message{sgr0}\n"
        ));
        die();
    }

    function parseArgs($argv) {
        $options = $args = array();
        $argv = array_slice($argv, 0);
        while ($arg = array_shift($argv)) {
            if (strpos($arg, '=') !== false) {
                list($arg, $value) = explode('=', $arg, 2);
                array_unshift($argv, $value);
            }
            $found = false;
            foreach ($this->options as $opt) {
                if ($opt->short == $arg || $opt->long == $arg) {
                    if ($opt->handleValue($options, $argv))
                        array_shift($argv);
                    $found = true;
                }
            }
            if (!$found && $arg[0] != '-')
                $args[] = $arg;
        }
        return array($options, $args);
    }
}
