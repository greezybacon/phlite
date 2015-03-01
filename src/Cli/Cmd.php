<?php

namespace Phlite\Cli;

use Phlite\Cli\KeyboardInterrupt;
use Phlite\Io\BufferedInputStream;

// TODO: Use an app to start the autoloader
require_once dirname(dirname(__file__)) . '/Io/InputStream.php';
require_once dirname(dirname(__file__)) . '/Io/BufferedInputStream.php';

class Cmd {

    var $prompt = '(cmd) ';
    var $identchars =
        'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_';
    var $ruler = "=";
    var $lastcmd = "";
    var $intro = null;
    var $doc_leader = false;
    var $doc_header = "Documented commands (type help <topic>)";
    var $misc_header = "Miscellaneous help topics:";
    var $undoc_header = "Undocumented commands";
    var $nohelp = "*** No help on %s";
    var $completekey = false;

    function __construct($completekey='tab' ,$stdin=false, $stdout=false) {
        $this->stdin = $stdin !== false ? $stdin : new BufferedInputStream('php://stdin');
        $this->stdout = $stdout !== false ? $stdout : fopen('php://output', 'w');
        $this->cmdqueue = array();
        if (function_exists('readline'))
            $this->completekey = $completekey;
    }

    function cmdloop($intro=false) {
        $this->preloop();
        if ($this->completekey) {
            readline_completion_function(array($this, 'complete'));
        }
        if ($intro)
            $this->intro = $intro;

        if ($this->intro)
            fwrite($this->stdout, $this->intro . "\n");

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, function() {
                throw new Exception\KeyboardInterrupt();
            });
        }

        $stop = null;
        while (!$stop) {
            if ($this->cmdqueue) {
                $line = array_pop($this->cmdqueue);
            }
            elseif ($this->completekey) {
                $line = readline($this->prompt);
                if ($line)
                    readline_add_history($line);
                elseif ($line === false)
                    $line = 'EOF';
            }
            else {
                fwrite($this->stdout, $this->prompt);
                fflush($this->stdout);
                $line = $this->stdin->readline();
                if (!$line)
                    $line = 'EOF';
                else
                    $line = rtrim($line, "\r\n");
            }
            if (function_exists('pcntl_signal_dispatch'))
                pcntl_signal_dispatch();
            $line = $this->precmd($line);
            $stop = $this->onecmd($line);
            $stop = $this->postcmd($stop, $line);
        }
        $this->postloop();
    }

    function precmd($line) {
        return $line;
    }

    function postcmd($stop, $line) {
        return $stop;
    }

    function preloop() {
    }

    function postloop() {
    }

    function parseline($line) {
        $line = trim($line);
        if (!$line) {
            return array(null, null, $line);
        }
        elseif ($line[0] == '?') {
            $line = "help " . substr($line, 1);
        }
        elseif ($line[0] == '!') {
            if (method_exists($this, 'do_shell')) {
                $line = "shell " . substr($line, 1);
            }
            else {
                return array(null, null, $line);
            }
        }
        $i = 0;
        $n = strlen($line);
        while ($i < $n && false !== strpos($this->identchars, $line[$i]))
            $i++;
        $cmd = substr($line, 0, $i);
        $arg = substr($line, $i);
        return array($cmd, $arg, $line);
    }

    function onecmd($line) {
        list($cmd, $arg, $line) = $this->parseline($line);
        if (!$line) {
            return $this->emptyline();
        }
        if ($cmd === null) {
            return $this->def($line);
        }
        $this->lastcmd = $line;
        if ($line == 'EOF')
            $this->lastcmd = '';
        try {
            if ($cmd == '') {
                return $this->def($line);
            }
            else {
                if (method_exists($this, 'do_' . $cmd)) {
                    return call_user_func(array($this, 'do_' . $cmd), $arg);
                }
                else {
                    return $this->def($line);
                }
            }
        }
        catch (Exception $e) {
            print $e->getTraceAsString();
        }
    }

    function emptyline() {
        if ($this->lastcmd) {
            return $this->onecmd($this->lastcmd);
        }
    }

    function def($line) {
        fwrite($this->stdout, sprintf("*** Unknown syntax: %s\n", $line));
    }

    function completedefault($text, $line, $start, $end) {
        return array();
    }

    function completenames($text) {
        $dotext = 'do_' . $text;
        $names = array();
        foreach ($this->get_names() as $a) {
            if (strpos($a, $dotext) === 0)
                $names[] = substr($a, 3);
        }
        return $names;
    }

    function complete($text, $state) {
        $info = readline_info();
        $origline = substr($info['line_buffer'], 0 , $info['end']);
        $line = ltrim($origline);
        $stripped = strlen($origline) - strlen($line);
        $begidx = $info['point'] - $stripped;
        $endidx = $info['end'] - $stripped;
        if ($begidx > 0) {
            list($cmd,) = $this->parseline($line);
            if ($cmd == '')
                $compfunc = 'completedefault';
            elseif (method_exists($this, 'complete_' . $cmd))
                $compfunc = 'complete_' . $cmd;
            else
                $compfunc = 'completedefault';
        }
        else {
            $compfunc = 'completedefault';
        }
        return call_user_func(
            array($this, $compfunc), $text, $line, $begidx, $endidx)
            ?: null;
    }

    function get_names() {
        return get_class_methods($this);
    }

    function do_help($arg) {
        if ($arg) {
            if (method_exists($this, 'help_' . $arg)) {
                call_user_func($this, 'help_' . $arg);
            } else {
                fwrite($this->stdout, sprintf("%s\n",
                    sprintf($this->nohelp, $arg)));
            }
        }
        else {
            $names = $this->get_names();
            $cmds_doc = $cmds_undoc = $help = array();
            foreach ($names as $name) {
                if (substr($name, 0, 5) == 'help_')
                    $help[substr($name, 5)] = 1;
            }
            sort($names);
            $prevname = '';
            foreach ($names as $name) {
                if (substr($name, 0, 3) == 'do_') {
                    if ($name == $prevname)
                        continue;
                    $prevname = $name;
                    $cmd = substr($name, 3);
                    if (isset($help[$cmd])) {
                        $cmds_doc[] = $cmd;
                        unset($help[$cmd]);
                    }
                    else {
                        $cmds_undoc[] = $cmd;
                    }
                }
            } 
            fwrite($this->stdout, sprintf("%s\n", $this->doc_leader));
            $this->print_topics($this->doc_header, $cmds_doc, 15, 80);
            $this->print_topics($this->misc_header, array_keys($help), 15,
                80);
            $this->print_topics($this->undoc_header, $cmds_undoc, 15, 80);
        }
    }

    function print_topics($header, $cmds, $cmdlen, $maxcol) {
        if ($cmds) {
            fwrite($this->stdout, sprintf("%s\n", $header));
            if ($this->ruler)
                fwrite($this->stdout, sprintf("%s\n",
                    str_repeat($this->ruler, strlen($header))));
            $this->columnize($cmds, $maxcol - 1);
            fwrite($this->stdout, "\n");
        }
    }

    function columnize($list, $displaywidth=80) {
        if (!$list) {
            fwrite($this->stdout, "<empty>\n");
            return;
        }
        // TODO: Error on non-strings in $list

        $size = count($list);
        if ($size == 1)
            fwrite($this->stdout, sprintf("%s\n", $list[0]));

        foreach (range(1, $size) as $nrows) {
            $ncols = ($size + $nrows - 1);
            $colwidths = array();
            $totwidth = -2;
            foreach (range($ncols) as $col) {
                $colwidth = 0;
                // ...
            }
        }
    }
}
