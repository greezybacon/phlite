<?php

namespace Phlite\I18n;

class Compat {

    static function shim() {
        // Load shims for mb_* functions
        if (!extension_loaded('mbstring')) {
            if (function_exists('iconv')) {
                function mb_strpos($a, $b) { return iconv_strpos($a, $b); }
                function mb_strlen($str) { return iconv_strlen($str); }
                function mb_substr($a, $b, $c=null) {
                    return iconv_substr($a, $b, $c); }
                function mb_convert_encoding($str, $to, $from='utf-8') {
                    return iconv($from, $to, $str); }
            }
            else {
                function mb_strpos($a, $b) {
                    $c = preg_replace('/^(\X*)'.preg_quote($b).'.*$/us', '$1', $a);
                    return ($c===$a) ? false : mb_strlen($c);
                }
                function mb_strlen($str) {
                    $a = array();
                    return preg_match_all('/\X/u', $str, $a);
                }
                function mb_substr($a, $b, $c=null) {
                    return preg_replace(
                        "/^\X{{$b}}(\X".($c ? "{{$c}}" : "*").").*/us",'$1',$a);
                }
                function mb_convert_encoding($str, $to, $from='utf-8') {
                    if (strcasecmp($to, $from) == 0)
                        return $str;
                    elseif (in_array(strtolower($to), array(
                            'us-ascii','latin-1','iso-8859-1'))
                            && function_exists('utf8_encode'))
                        return utf8_encode($str);
                    else
                        return $str;
                }
            }
            define('LATIN1_UC_CHARS', 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝ');
            define('LATIN1_LC_CHARS', 'àáâãäåæçèéêëìíîïðñòóôõöøùúûüý');
            function mb_strtoupper($str) {
                if (is_array($str)) $str = $str[0];
                return strtoupper(strtr($str, LATIN1_LC_CHARS, LATIN1_UC_CHARS));
            }
            function mb_strtolower($str) {
                if (is_array($str)) $str = $str[0];
                return strtolower(strtr($str, LATIN1_UC_CHARS, LATIN1_LC_CHARS));
            }
            define('MB_CASE_LOWER', 1);
            define('MB_CASE_UPPER', 2);
            define('MB_CASE_TITLE', 3);
            function mb_convert_case($str, $mode) {
                // XXX: Techincally the calls to strto...() will fail if the
                //      char is not a single-byte char
                switch ($mode) {
                case MB_CASE_LOWER:
                    return preg_replace_callback('/\p{Lu}+/u', 'mb_strtolower', $str);
                case MB_CASE_UPPER:
                    return preg_replace_callback('/\p{Ll}+/u', 'mb_strtoupper', $str);
                case MB_CASE_TITLE:
                    return preg_replace_callback('/\b\p{Ll}/u', 'mb_strtoupper', $str);
                }
            }
            function mb_internal_encoding($encoding) { return 'UTF-8'; }
            function mb_regex_encoding($encoding) { return 'UTF-8'; }
            function mb_substr_count($haystack, $needle) {
                $matches = array();
                return preg_match_all('`'.preg_quote($needle).'`u', $haystack,
                    $matches);
            }
        }
        else {
            // Use UTF-8 for all multi-byte string encoding
            mb_internal_encoding('utf-8');
        }
        if (extension_loaded('iconv'))
            iconv_set_encoding('internal_encoding', 'UTF-8');
    }
}
