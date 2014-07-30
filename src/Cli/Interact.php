<?php

namespace Phlite\Cli;

  require_once 'Cmd.php';
class Interact extends Cmd {

    var $prompt = ">>> ";
    var $prompt1 = ">>> ";
    var $prompt2 = "... ";
    var $buffer = "";
    var $scope = array();

    function __construct($completekey='tab', $stdin=false, $stdout=false) {
        $this->intro = sprintf(<<<EOT
PHP %s (%s), on %s
Type "help" for more information
EOT
        , PHP_VERSION, PHP_SAPI, PHP_OS);
        parent::__construct($completekey, $stdin, $stdout);
    }

    function unknown($___line) {
        // Refresh previous scope
        foreach ($this->scope as $___k => &$___v)
            $$___k = & $___v;
        unset($___k);
        unset($___v);
        // If the line is an expression, capture the result
        if ($this->isExpr)
            $___line = 'return ' . $___line;
        // Eval the current line
        try {
            $___result = eval($___line);
        } 
        catch (Exception $e) {
            fwrite(STDERR, $e->getTraceAsString());
        }
        // Capture new scope after evaluation
        $this->scope = get_defined_vars();
        // Output expression result
        if ($___result !== null) {
            fwrite($this->stdout, var_export($___result, true) . "\n");
            $this->scope['_'] = $___result;
        }
        unset($this->scope['___line']);
        unset($this->scope['___result']);
        unset($this->scope['this']);
    }

    function do_EOF($arg) {
        return true;
    }

    function precmd($line) {
        if (!$this->isComplete($line, $this->isExpr)) {
            $this->buffer .= $line . "\n";
            $this->prompt = $this->prompt2;
            return '';
        }
        else {
            $this->prompt = $this->prompt1;
        }
        $line = $this->buffer . $line . "\n;";
        $this->buffer = '';
        return $line;
    }

    function emptyline() {
    }

    function isComplete($line, &$expression) {
        // Detect unclosed quotes and braces
        $tokens = token_get_all('<?php ' . $this->buffer . $line . "\n");
        $braces = $parens = $quotes = $heredoc = 0;
        $expression = true;
        while (list($i,$token) = each($tokens)) {
            switch ($token[0]) {
                case '{';
                    $expression = false;
                case T_DOLLAR_OPEN_CURLY_BRACES:
                case T_CURLY_OPEN:
                    $braces++;
                    break;
                case '}';
                    $braces--;
                    break;
                case '(':
                    $parens++;
                    break;
                case ')':
                    $parens--;
                    break;
                case '"':
                    $quotes++;
                    break;
                case T_START_HEREDOC:
                    $heredoc = 1;
                    break;
                case T_END_HEREDOC:
                    $heredoc = 0;
                    break;
                case T_FOR:
                case T_IF:
                case T_FOREACH:
                case T_STATIC:
                case T_GLOBAL:
                case T_PRINT:
                case T_RETURN:
                case '=':
                    $expression = false;
            }
        }
        return $braces + $parens + $heredoc + ($quotes % 2) == 0;
    }

    function completedefault($text, $line, $start, $end) {
        // See what comes before $text
        if (($start > strlen($text))
                && $line[$start - strlen($text) - 1] == '$')
            return $this->completevar($text, $line, $start, $end);

        $tokens = token_get_all('<?php ' . $line);
        $i = 3;
        while (count($tokens) && $i--) {
            $last = array_pop($tokens);
            switch ($last[0]) {
            case T_OBJECT_OPERATOR:
                $obj = array_pop($tokens);
                if ($obj[0] != T_VARIABLE)
                    break;
                $var = substr($obj[1], 1);
                if (!isset($this->scope[$var]))
                    break;
                $o = &$this->scope[$var];
                return $this->completeobject($o, $text, $line, $start,
                    $end);
            case T_PAAMAYIM_NEKUDOTAYIM:
                $obj = array_pop($tokens);
                return $this->completeclass($obj[1], $text, $line, $start,
                    $end);
            case T_NEW:
                return $this->completeclasslist($text, $line, $start, $end);
            case '[':
                $obj = array_pop($tokens);
                if ($obj[0] != T_VARIABLE)
                    break;
                $var = substr($obj[1], 1);
                if (!isset($this->scope[$var]))
                    break;
                $array = &$this->scope[$var];
                if (is_array($array))
                    return $this->completearrayget($array, $text, $line, $start, $end);
            }
        }
        return $this->completefunction($text, $line, $start, $end);
    }
    
    function completefunction($text, $line, $start, $end) {
        $matches = array('include', 'require', 'print', 'echo');
        foreach (get_defined_functions() as $section=>$list) {
            foreach ($list as $func) {
                if (!$text || stripos($func, $text) === 0)
                    $matches[] = $func . '(';
            }
        }
        foreach (get_declared_classes() as $class) {
            if (!$text || stripos($class, $text) === 0)
                $matches[] = $class;
        }
        return $matches;
    }

    function completeclass($class, $text, $line, $start, $end) {
        // Figure out what is the class for the object in question
        $matches = array();
        if ($class[0] == '$')
            $class = get_class($this->scope[substr($class, 1)]);
        if (!$class)
            return $matches;

        // Find :: in text and fetch the RHS
        list($lhs, $rhs) = explode('::', $text, 2);

        foreach (get_class_methods($class) as $method) {
            if (!$rhs || stripos($method, $rhs) === 0)
                $matches[] = $lhs.'::'.$method . '(';
        }
        return $matches;
    }

    function completeobject($object, $text, $line, $start, $end) {
        $matches = array();
        if (!is_object($object))
            return $matches;
        $class = get_class($object);
        if (!$class)
            return $matches;

        foreach (get_class_methods($class) as $method) {
            if (!$text || stripos($method, $text) === 0)
                $matches[] = $method . '(';
        }
        foreach (get_object_vars($object) as $var) {
            if (!$text || stripos($var, $text) === 0)
                $matches[] = $var;
        }
        // TODO: Static properties
        return array_filter($matches);
    }

    function completevar($text, $line, $start, $end)  {
        $matches = array();
        foreach (array_keys($this->scope) as $name) {
            if (!$text || stripos($name, $text) === 0)
                $matches[] = $name;
        }
        return $matches;
    }

    function completeclasslist($text, $line, $start, $end) {
        $matches = array();
        foreach (get_declared_classes() as $class) {
            if (!$text || stripos($class, $text) === 0)
                $matches[] = $class;
        }
        return $matches;
    }

    function completearrayget($array, $text, $line, $start, $end) {
        $matches = array();
        $quote = substr($line, -strlen($text) - 1, 1);
        foreach (array_keys($array) as $k) {
            if (!$text || strpos($k, $text) === 0)
                $matches[] = $k . $quote . "]";
        }
        return $matches;
    }

    function complete_include($text, $line, $start, $end) { return glob('*'); }
    function complete_include_once($text, $line, $start, $end) { return glob('*'); }
    function complete_require($text, $line, $start, $end) { return glob('*'); }
    function complete_require_once($text, $line, $start, $end) { return glob('*'); }
}

// Allow running directly
do {
  require_once 'Cmd.php';
  if (PHP_SAPI != 'cli') break;
  if (empty ($_SERVER['PHP_SELF'])
        || FALSE === strpos($_SERVER['PHP_SELF'], basename(__file__)) )
    break;
  $i = new Interact();
  $i->cmdloop();
} while (0);
