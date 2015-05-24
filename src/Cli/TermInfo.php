<?php

namespace Phlite\Cli;

/**
 * A terminfo compiler and interpreter for the terminfo library available
 * on most Unix distributions. A new instance can be created via the
 * ::forTerminal() method which will auto inspect and lazily compile the
 * terminfo capabilities for the current terminal from the operating system.
 *
 * This implementation currently relies on `infocmp` to provide access to
 * the terminfo capability strings. No support is currently available for
 * Windows.
 *
 * The capabilities can be accessed via simple property and method access,
 * or by utilizing the ::template() method. For instance, on an ANSI terminal
 * such as xterm, one might observe:
 *
 * >>> $TI = Terminfo::forTerminal();
 * >>> $TI->setaf(Terminfo::DARKRED);
 * "\x1b[31m"
 * >>> $TI->sgr0
 * "\x1b(B"
 * >>> $TI->template('{setaf:RED}Hello, world!{sgr0}')
 * "\x1b[91mHello, world!\x1b(B"
 */
class TermInfo {
    
    var $name;
    var $description;

    // TODO: Provide defaults as empty strings to provide gracefull fallbacks
    //       to noops while also crashing for invalid tput commands
    var $caps = array();

    // ANSI colors
    const BLACK     = 0;
    const DARKRED   = 1;
    const DARKGREEN = 2;
    const BROWN     = 3;
    const DARKYELLOW = 3;
    const DARKBLUE  = 4;
    const DARKMAGENTA = 5;
    const DARKCYAN  = 6;
    const GRAY      = 7;
    const DARKGRAY  = 8;
    const RED       = 9;
    const GREEN     = 10;
    const YELLOW    = 11;
    const BLUE      = 12;
    const MAGENTA   = 13;
    const CYAN      = 14;
    const WHITE     = 15;

    static protected $terminfos = array();

    private function __construct($caps, $name='dumb') {
        $this->caps = $caps;
        $this->name = $name;
    }
    
    static function dumb() {
        return new static(array());
    }
    
    /**
     * Create a Terminfo instance for the declared TERM name or the term
     * currently set up in the environment. The result is cached, so multiple
     * calls with the same $TERM will return the same Terminfo instance.
     */
    static function forTerminal($TERM=false) {
        // TODO: Support varying TERM string

        // Cache the result for future calls with the same $TERM
        if (isset(self::$terminfos[$TERM]))
            return self::$terminfos[$TERM];
        
        // Attempt to read from `infocmp`
        if (!($info = popen('infocmp -I', 'r')))
            // Provide some fallback for ANSI, or Windows terminals
            return new static;
        
        do {
            $L = fgets($info);
            if ($L[0] != '#')
                @list($name, $description) = explode('|', $L, 2);
        }
        while (!isset($name));
        
        $matches = array();
        if (!preg_match_all('`\s+(\w+)(?:[=#]([^,]+))?,`', fread($info, 4096), $matches, PREG_SET_ORDER))
            return;

        $caps = array();
        foreach ($matches as $C) {
            if (isset($C[2])) {
                // Unescape escape sequences
                $caps[$C[1]] = preg_replace_callback('`\^\w|\\\\E`', function($m) {
                    if ($m[0][0] == '^')
                        return chr(ord($m[0][0]) - 64);
                    elseif ($m[0][1] == 'E')
                        return chr(27);
                }, $C[2]);
            }
            else {
                $caps[$C[1]] = true;
            }
        }
        return self::$terminfos[$TERM] = new static($caps, $name);
    }
    
    function __isset($cap) {
        return isset($this->caps[$cap]);
    }
    
    function __get($cap) {
        return @$this->caps[$cap] ?: '';
    }
    
    function __call($cap, $args) {        
        if (!isset($this->caps[$cap])) {
            // Graceful degrade for now
            return '';
            throw new \InvalidArgumentException('No such capability');
        }
        
        if (!is_callable($this->caps[$cap])) {
            $this->caps[$cap] = $this->compile_terminfo($this->caps[$cap]);
            if (!is_callable($this->caps[$cap]))
                throw new \InvalidArgumentException('Capability does not take arguments');
        }
        return $this->caps[$cap]($args);
    }
    
    /**
     * Replace terminal commands in a template string:
     * <{setaf:RED}Hello World{sgr0}>
     * 
     * Parameters are supported in the templates if the capability name is
     * followed by a colon (:) along with comma-separated parameters. Any
     * constants defined in the Terminfo class (like the colors) are also
     * supported and will be automatically dereferenced.
     *
     * Open and close brace can be escaped by doublint (`{{`).
     */
    function template($string) {
        $self = $this;
        $class = new \ReflectionClass($this);
        $consts = $class->getConstants();
        $templated = preg_replace_callback('`(?<!{)\{(\w+)(?::([^\}]+))?\}`',
        function($m) use ($self, $consts) {
            if (isset($m[2])) {
                $args = explode(',', $m[2]);
                foreach ($args as $i=>$A) {
                    $A = trim($A);
                    if (is_numeric($A))
                        $args[$i] = (int) $A;
                    elseif (isset($consts[$A]))
                        $args[$i] = $consts[$A];
                }
                return $self->__call($m[1], $args);
            }
            elseif (isset($m[1]))
                return $self->__get($m[1]);
            else
                // Return first char of doubled braces
                return $m[0][0];
        }, $string);
        
        return str_replace(array('{{','}}'), array('{','}'), $templated);
    }
    
    /**
     * Compiles a terminfo string to a PHP callable function. The functions
     * arguments are interpreted according to the terminfo string, and the
     * result of the function is the bytes which should be sent to the
     * terminal.
     */
    protected function compile_terminfo($expr) {        
        // `man terminfo` for details on the %X sequences
        
        static $simple = array(
            // %%   outputs `%'
            '%' => '$O[]="%";',
            // %c print pop() like %c in printf
            'c' => '$O[]=chr(array_pop($S));',
            'd' => '$O[]=array_pop($S);',
            // %+ %- %* %/ %m
            // arithmetic (%m is mod): push(pop() op pop())
            '+' => '$S[]=array_pop($S)+array_pop($S);',
            '-' => '$_=array_pop($S);$S[]=array_pop($S)-$_;',
            '*' => '$S[]=array_pop($S)*array_pop($S);',
            '/' => '$_=array_pop($S);$S[]=array_pop($S)/$_;',
            'm' => '$_=array_pop($S);$S[]=array_pop($S)%$_;',
            // %& %| %^
            // bit operations (AND, OR and exclusive-OR): push(pop() op pop())
            '&' => '$S[]=array_pop($S)&array_pop($S);',
            '|' => '$S[]=array_pop($S)|array_pop($S);',
            '^' => '$S[]=array_pop($S)^array_pop($S);',
            // %= %> %<
            // logical operations: push(pop() op pop())
            // Flip the comparision b/c pops will reverse the operands
            '<' => '$S[]=array_pop($S)>array_pop($S);',
            '>' => '$S[]=array_pop($S)<array_pop($S);',
            '=' => '$S[]=array_pop($S)==array_pop($S);',
            // %A, %O
            // logical AND and OR operations (for conditionals)
            'A' => '$S[]=array_pop($S)&&array_pop($S);',
            'O' => '$S[]=array_pop($S)||array_pop($S)',
            // %! %~
            // unary operations (logical and bit complement): push(op pop())
            '!' => '$S[]=!array_pop($S);',
            '~' => '$S[]=~array_pop($S);',
            // %? expr %t thenpart %e elsepart %;
            // This forms an if-then-else.  The %e elsepart is optional. 
            // Usually the %?  expr  part  pushes a value  onto the stack, 
            // and %t pops it from the stack, testing if it is nonzero (true).
            // If it is zero (false), control passes to the %e (else) part.
            '?' => '', // continue to process the if statement
            'e' => '}else{',
            'l' => '$S[]=strlen(array_pop($S));',
        );
        
        // Prologue
        $cmds = array(
            // Arguments are stored in $A, $V is stack vars, $O is output,
            // and $S is the stack
            '$V=$S=$O=array();',
        );
        
        $func = $pos = $braces = 0;
        $len = strlen($expr);
        $buffer = '';
        while ($pos < $len) {
            if ('%' != ($T = $expr[$pos++])) {
                // Not a `%` sequence, add to output buffer
                $buffer .= $T;
            }
            else { 
                if ($buffer) {
                    $cmds[] = "\$O[]='".str_replace("'", "\\'", $buffer)."';";
                    $buffer = '';
                }
                $T = $expr[$pos++];
                if (isset($simple[$T])) {
                    $cmds[] = $simple[$T];
                }
                else switch ($T) {
                case 'p':
                    // %p[1-9]
                    // push i'th parameter
                    $n = ((int) $expr[$pos++]) - 1;
                    if (substr($expr, $pos, 2) == '%d') {
                        // Optimization for `%p1%d`
                        $cmds[] = '$O[]=$A['.$n.'];';
                        $pos += 2;
                    }
                    else
                        $cmds[] = '$S[]=$A['.$n.'];'; break;
                case '{':
                    // %{nn}
                    // integer constant nn
                    $rest = '';
                    while ('}' != ($X = $expr[$pos++]))
                        $rest .= $X;
                    $cmds[] = "\$S[]={$rest};"; break;
                case 'P':
                    // %P[a-z]
                    // set dynamic variable [a-z] to pop()
                    $cmds[] = '$V["'.$expr[$pos++].'"]=array_pop($S);'; break;
                case 'g':
                    // %g[a-z]
                    // get dynamic variable [a-z] and push it
                    $cmds[] = '$S[]=$V["'.$expr[$pos++].'"]'; break;
                case 'i':
                    // The interpretation is really difficult:
                    // %i — add 1 to first two parameters (for ANSI terminals)
                    // What's a "parameter"?
                    $cmds[] = '$A[0]++;@$A[1]++;';
                    break;
                case ';':
                    // End of if-then-else
                    $cmds[] = '}';
                    $braces--;
                    break;
                case 't': 
                    // if (true) part of if-then-else
                    $cmds[] = 'if(array_pop($S)){';
                    $braces++;
                    break;
                default:
                    // The default is assumed to be avalid printf token using
                    // the top-of-stack
                    $rest = "%$T";
                    while (false === strpos('doXxs', $T)) {
                        $T = $expr[$pos++];
                        $rest .= $T;
                    }
                    $cmds[] = '$O[]=sprintf("'.$rest.'",array_pop($S));'; break;
                }
                $func = true;
            }
        }
        if (!$func)
            return $expr;
        
        if ($braces)
            $cmds[] = str_repeat('}', $braces);
        if ($buffer)
            $cmds[] = "\$O[]='".str_replace("'", "\\'", $buffer)."';";

        $cmds[] = 'return implode("",$O);';

        // All arguments are passes as an array (from __call)
        return create_function('$A', implode('', $cmds));
    }
}