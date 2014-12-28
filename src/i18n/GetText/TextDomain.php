<?php

namespace Phlite\I18n;

if (!defined('LC_MESSAGES')) {
    define('LC_ALL', 0);
    define('LC_CTYPE', 1);
    define('LC_NUMERIC', 2);
    define('LC_TIME', 3);
    define('LC_COLLATE', 4);
    define('LC_MONETARY', 5);
    define('LC_MESSAGES', 6);
}

class TextDomain {
    var $l10n = array();
    var $path;
    var $codeset;
    var $domain;

    static $registry;
    static $default_domain = 'messages';
    static $current_locale = '';
    static $LC_CATEGORIES = array(
        LC_ALL => 'LC_ALL',
        LC_CTYPE => 'LC_CTYPE',
        LC_NUMERIC => 'LC_NUMERIC',
        LC_TIME => 'LC_TIME',
        LC_COLLATE => 'LC_COLLATE',
        LC_MONETARY => 'LC_MONETARY',
        LC_MESSAGES => 'LC_MESSAGES'
    );

    function __construct($domain) {
        $this->domain = $domain;
    }

    function getTranslation($category=LC_MESSAGES, $locale=false) {
        $locale = $locale ?: self::$current_locale
            ?: self::setLocale(LC_MESSAGES, 0);

        if (isset($this->l10n[$locale]))
            return $this->l10n[$locale];

        if ($locale == 'en_US') {
            $this->l10n[$locale] = new Translation(null);
        }
        else {
            // get the current locale
            $bound_path = @$this->path ?: './';
            $subpath = self::$LC_CATEGORIES[$category] .
                '/'.$this->domain.'.mo.php';

            // APC short-circuit (if supported)
            $key = sha1($locale .':lang:'. $subpath);
            if ($T = Translation::resurrect($key)) {
                return $this->l10n[$locale] = $T;
            }

            $locale_names = self::get_list_of_locales($locale);
            $input = null;
            foreach ($locale_names as $T) {
                if (substr($bound_path, 7) != 'phar://') {
                    $phar_path = 'phar://' . $bound_path . $T . ".phar/" . $subpath;
                    if (file_exists($phar_path)) {
                        $input = $phar_path;
                        break;
                    }
                }
                $full_path = $bound_path . $T . "/" . $subpath;
                if (file_exists($full_path)) {
                    $input = $full_path;
                    break;
                }
            }
            // TODO: Handle charset hint from the environment
            $this->l10n[$locale] = $T = new Translation($input);
            $T->cache($key);
        }
        return $this->l10n[$locale];
    }

    function setPath($path) {
        $this->path = $path;
    }

    static function configureForUser($user=false) {
        $lang = Internationalization::getCurrentLanguage($user);

        $info = Internationalization::getLanguageInfo(strtolower($lang));
        if (!$info)
            // Not a supported language
            return;

        // Define locale for C-libraries
        putenv('LC_ALL=' . $info['code']);
        self::setLocale(LC_ALL, $info['code']);
    }

    static function setDefaultDomain($domain) {
        static::$default_domain = $domain;
    }

    /**
     * Returns passed in $locale, or environment variable $LANG if $locale == ''.
     */
    static function get_default_locale($locale='') {
        if ($locale == '') // emulate variable support
            return getenv('LANG');
        else
            return $locale;
    }

    static function get_list_of_locales($locale) {
        /* Figure out all possible locale names and start with the most
         * specific ones.  I.e. for sr_CS.UTF-8@latin, look through all of
         * sr_CS.UTF-8@latin, sr_CS@latin, sr@latin, sr_CS.UTF-8, sr_CS, sr.
        */
        $locale_names = $m = array();
        $lang = null;
        if ($locale) {
            if (preg_match("/^(?P<lang>[a-z]{2,3})"              // language code
                ."(?:_(?P<country>[A-Z]{2}))?"           // country code
                ."(?:\.(?P<charset>[-A-Za-z0-9_]+))?"    // charset
                ."(?:@(?P<modifier>[-A-Za-z0-9_]+))?$/",  // @ modifier
                $locale, $m)
            ) {

            if ($m['modifier']) {
                // TODO: Confirm if Crowdin uses the modifer flags
                if ($m['country']) {
                    $locale_names[] = "{$m['lang']}_{$m['country']}@{$m['modifier']}";
                }
                $locale_names[] = "{$m['lang']}@{$m['modifier']}";
            }
            if ($m['country']) {
                $locale_names[] = "{$m['lang']}_{$m['country']}";
            }
            $locale_names[] = $m['lang'];
        }

        // If the locale name doesn't match POSIX style, just include it as-is.
        if (!in_array($locale, $locale_names))
            $locale_names[] = $locale;
      }
      return array_filter($locale_names);
    }

    static function setLocale($category, $locale) {
        if ($locale === 0) { // use === to differentiate between string "0"
            if (self::$current_locale != '')
                return self::$current_locale;
            else
                // obey LANG variable, maybe extend to support all of LC_* vars
                // even if we tried to read locale without setting it first
                return self::setLocale($category, self::$current_locale);
        } else {
            if (function_exists('setlocale')) {
              $ret = setlocale($category, $locale);
              if (($locale == '' and !$ret) or // failed setting it by env
                  ($locale != '' and $ret != $locale)) { // failed setting it
                // Failed setting it according to environment.
                self::$current_locale = self::get_default_locale($locale);
              } else {
                self::$current_locale = $ret;
              }
            } else {
              // No function setlocale(), emulate it all.
              self::$current_locale = self::get_default_locale($locale);
            }
            return self::$current_locale;
        }
    }

    static function lookup($domain=null) {
        if (!isset($domain))
            $domain = self::$default_domain;
        if (!isset(static::$registry[$domain])) {
            static::$registry[$domain] = new TextDomain($domain);
        }
        return static::$registry[$domain];
    }
}