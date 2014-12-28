<?php
    
namespace Phlite\I18n;

/**
 * Class: Translation
 *
 * This class is strongly based on the MoReader class. It makes use of
 * a few simple optimizations for the context of Phlite
 *
 *    * The language packs are pre-compiled and distributed (which means
 *      they can be customized).
 *    * The MO file will always be processed by PHP code
 *    * osTicket uses utf-8 output exclusively (for web traffic anyway)
 *
 * These allow us to optimize the MO file for the osTicket project
 * specifically and make enough of an optimization to allow using a pure-PHP
 * source gettext library implementation which should be roughly the same
 * performance as the libc gettext library.
 */
class Translation extends MoReader implements Serializable {

    var $charset;

    const META_HEADER = 0;

    function __construct($reader, $charset=false) {
        if (!$reader)
            return $this->short_circuit = true;

        // Just load the cache
        if (!is_string($reader))
            throw new RuntimeException('Programming Error: Expected filename for translation source');
        $this->STREAM = $reader;

        $this->enable_cache = true;
        $this->charset = $charset;
        $this->encode = $charset && strcasecmp($charset, 'utf-8') !== 0;
        $this->load_tables();
    }

    function load_tables() {
        if (isset($this->cache_translations))
            return;

        $this->cache_translations = (include $this->STREAM);
    }

    function translate($string) {
        if ($this->short_circuit)
            return $string;

        // Caching enabled, get translated string from cache
        if (isset($this->cache_translations[$string]))
            $string = $this->cache_translations[$string];

        if (!$this->encode)
            return $string;

        return Format::encode($string, 'utf-8', $this->charset);
    }

    /**
     * buildHashFile
     *
     * This constructs a file, usually named something.mo.php, which will
     * contain a PHP hash array literal. The literal can be included later
     * and used by this class to provide a translation via functions
     * declared below such as __()
     */
    static function buildHashFile($mofile, $outfile=false, $return=false) {
        if (!$outfile) {
            $stream = fopen('php://stdout', 'w');
        }
        elseif (is_string($outfile)) {
            $stream = fopen($outfile, 'w');
        }
        elseif (is_resource($outfile)) {
            $stream = $outfile;
        }

        if (!$stream)
            throw new InvalidArgumentException(
                'Expected a filename or valid resource');

        if (!$mofile instanceof FileReader)
            $mofile = new FileReader($mofile);

        $reader = new parent($mofile, true);

        if ($reader->short_circuit || $reader->error)
            throw new Exception('Unable to initialize MO input file');

        $reader->load_tables();

        // Get basic table
        if (!($table = $reader->cache_translations))
            throw new Exception('Unable to read translations from file');

        // Transcode the table to UTF-8
        $header = $table[""];
        $info = array();
        preg_match('/^content-type: (.*)$/im', $header, $info);
        $charset = false;
        if ($content_type = $info[1]) {
            // Find the charset property
            $settings = explode(';', $content_type);
            foreach ($settings as $v) {
                @list($prop, $value) = explode('=', trim($v), 2);
                if (strtolower($prop) == 'charset') {
                    $charset = trim($value);
                    break;
                }
            }
        }
        if ($charset && strcasecmp($charset, 'utf-8') !== 0) {
            foreach ($table as $orig=>$trans) {
                // Format::encode defaults to UTF-8 output
                $source = new Unicode($orig, $charset);
                $trans = new Unicode($trans, $charset);
                $table[(string) $source->encode('utf-8')] =
                    (string) $trans->encode('utf-8');
                unset($table[$orig]);
            }
        }

        // Add in some meta-data
        $table[self::META_HEADER] = array(
            'Revision' => $reader->revision,      // From the MO
            'Total-Strings' => $reader->total,    // From the MO
            'Table-Size' => count($table),      // Sanity check for later
            'Build-Timestamp' => gmdate(DATE_RFC822),
            'Format-Version' => 'A',            // Support future formats
            'Encoding' => 'UTF-8',
        );

        // Serialize the PHP array and write to output
        $contents = sprintf('<?php return %s;', var_export($table, true));
        if ($return)
            return $contents;
        else
            fwrite($stream, $contents);
    }

    static function resurrect($key) {
        if (!function_exists('apc_fetch'))
            return false;

        $success = true;
        if (($translation = apc_fetch($key, $success)) && $success)
            return $translation;
    }
    function cache($key) {
        if (function_exists('apc_add'))
            apc_add($key, $this);
    }


    function serialize() {
        return serialize(array($this->charset, $this->encode, $this->cache_translations));
    }
    function unserialize($what) {
        list($this->charset, $this->encode, $this->cache_translations)
            = unserialize($what);
        $this->short_circuit = ! $this->enable_cache
            = 0 < count($this->cache_translations);
    }
}

// XXX: Place following functions in the root namespace

// Functions for gettext library. Since the gettext extension for PHP is not
// used as a fallback, there is no detection and compat funciton
// installation for the gettext library function calls.

function _gettext($msgid) {
    return TextDomain::lookup()->getTranslation()->translate($msgid);
}
function __($msgid) {
    return _gettext($msgid);
}
function _ngettext($singular, $plural, $number) {
    return TextDomain::lookup()->getTranslation()
        ->ngettext($singular, $plural, $number);
}
function _dgettext($domain, $msgid) {
    return TextDomain::lookup($domain)->getTranslation()
        ->translate($msgid);
}
function _dngettext($domain, $singular, $plural, $number) {
    return TextDomain::lookup($domain)->getTranslation()
        ->ngettext($singular, $plural, $number);
}
function _dcgettext($domain, $msgid, $category) {
    return TextDomain::lookup($domain)->getTranslation($category)
        ->translate($msgid);
}
function _dcngettext($domain, $singular, $plural, $number, $category) {
    return TextDomain::lookup($domain)->getTranslation($category)
        ->ngettext($singular, $plural, $number);
}
function _pgettext($context, $msgid) {
    return TextDomain::lookup()->getTranslation()
        ->pgettext($context, $msgid);
}
function _dpgettext($domain, $context, $msgid) {
    return TextDomain::lookup($domain)->getTranslation()
        ->pgettext($context, $msgid);
}
function _dcpgettext($domain, $context, $msgid, $category) {
    return TextDomain::lookup($domain)->getTranslation($category)
        ->pgettext($context, $msgid);
}
function _npgettext($context, $singular, $plural, $n) {
    return TextDomain::lookup()->getTranslation()
        ->npgettext($context, $singular, $plural, $n);
}
function _dnpgettext($domain, $context, $singular, $plural, $n) {
    return TextDomain::lookup($domain)->getTranslation()
        ->npgettext($context, $singular, $plural, $n);
}
function _dcnpgettext($domain, $context, $singular, $plural, $category, $n) {
    return TextDomain::lookup($domain)->getTranslation($category)
        ->npgettext($context, $singular, $plural, $n);
}