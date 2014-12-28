<?php

namespace Phlite\Cli;

class Option {

    var $default = false;

    function __construct($options=false) {
        list($this->short, $this->long) = array_slice($options, 0, 2);
        $this->help = (isset($options['help'])) ? $options['help'] : "";
        $this->action = (isset($options['action'])) ? $options['action']
            : "store";
        $this->dest = (isset($options['dest'])) ? $options['dest']
            : substr($this->long, 2);
        $this->type = (isset($options['type'])) ? $options['type']
            : 'string';
        $this->const = (isset($options['const'])) ? $options['const']
            : null;
        $this->default = (isset($options['default'])) ? $options['default']
            : null;
        $this->metavar = (isset($options['metavar'])) ? $options['metavar']
            : 'var';
        $this->nargs = (isset($options['nargs'])) ? $options['nargs']
            : 1;
    }

    function hasArg() {
        return $this->action != 'store_true'
            && $this->action != 'store_false';
    }

    function handleValue(&$destination, $args) {
        $nargs = 0;
        $value = ($this->hasArg()) ? array_shift($args) : null;
        if ($value[0] == '-')
            $value = null;
        elseif ($value)
            $nargs = 1;
        if ($this->type == 'int')
            $value = (int)$value;
        switch ($this->action) {
            case 'store_true':
                $value = true;
                break;
            case 'store_false':
                $value = false;
                break;
            case 'store_const':
                $value = $this->const;
                break;
            case 'append':
                if (!isset($destination[$this->dest]))
                    $destination[$this->dest] = array($value);
                else {
                    $T = &$destination[$this->dest];
                    $T[] = $value;
                    $value = $T;
                }
                break;
            case 'store':
            default:
                break;
        }
        $destination[$this->dest] = $value;
        return $nargs;
    }

    function toString() {
        $short = explode(':', $this->short);
        $long = explode(':', $this->long);
        if ($this->nargs === '?')
            $switches = sprintf('    %s [%3$s], %s[=%3$s]', $short[0],
                $long[0], $this->metavar);
        elseif ($this->hasArg())
            $switches = sprintf('    %s %3$s, %s=%3$s', $short[0], $long[0],
                $this->metavar);
        else
            $switches = sprintf("    %s, %s", $short[0], $long[0]);
        $help = preg_replace('/\s+/', ' ', $this->help);
        if (strlen($switches) > 23)
            $help = "\n" . str_repeat(" ", 24) . $help;
        else
            $switches = str_pad($switches, 24);
        $help = wordwrap($help, 54, "\n" . str_repeat(" ", 24));
        return $switches . $help;
    }
}
