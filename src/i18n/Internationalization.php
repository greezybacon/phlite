<?php

namespace Phlite\I18n;

<?php
/*********************************************************************
    class.i18n.php

    Internationalization and localization helpers for osTicket

    Peter Rotich <peter@osticket.com>
    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require_once INCLUDE_DIR.'class.error.php';
require_once INCLUDE_DIR.'class.yaml.php';

class Internationalization {

    // Languages in order of decreasing priority. Always use en_US as a
    // fallback
    var $langs = array('en_US');

    function Internationalization($language=false) {
        global $cfg;

        if ($cfg && ($lang = $cfg->getSystemLanguage()))
            array_unshift($this->langs, $language);

        // Detect language filesystem path, case insensitively
        if ($language && ($info = self::getLanguageInfo($language))) {
            array_unshift($this->langs, $info['code']);
        }
    }

    function getTemplate($path) {
        return new DataTemplate($path, $this->langs);
    }

    static function getLanguageDescription($lang) {
        global $thisstaff, $thisclient;

        $langs = self::availableLanguages();
        $lang = strtolower($lang);
        if (isset($langs[$lang])) {
            $info = &$langs[$lang];
            if (!isset($info['desc'])) {
                if (extension_loaded('intl')) {
                    $lang = self::getCurrentLanguage();
                    list($simple_lang,) = explode('_', $lang);
                    $info['desc'] = sprintf("%s%s",
                        // Display the localized name of the language
                        Locale::getDisplayName($info['code'], $info['code']),
                        // If the major language differes from the user's,
                        // display the language in the user's language
                        (strpos($simple_lang, $info['lang']) === false
                            ? sprintf(' (%s)', Locale::getDisplayName($info['code'], $lang)) : '')
                    );
                }
                else {
                    $info['desc'] = sprintf("%s%s (%s)",
                        $info['nativeName'],
                        $info['locale'] ? sprintf(' - %s', $info['locale']) : '',
                        $info['name']);
                }
            }
            return $info['desc'];
        }
        else
            return $lang;
    }

    static function getLanguageInfo($lang) {
        $langs = self::availableLanguages();
        return @$langs[strtolower($lang)] ?: array();
    }

    static function availableLanguages($base=I18N_DIR) {
        static $cache = false;
        if ($cache) return $cache;

        $langs = (include I18N_DIR . 'langs.php');

        // Consider all subdirectories and .phar files in the base dir
        $dirs = glob(I18N_DIR . '*', GLOB_ONLYDIR | GLOB_NOSORT);
        $phars = glob(I18N_DIR . '*.phar', GLOB_NOSORT) ?: array();

        $installed = array();
        foreach (array_merge($dirs, $phars) as $f) {
            $base = basename($f, '.phar');
            @list($code, $locale) = explode('_', $base);
            if (isset($langs[$code])) {
                $installed[strtolower($base)] =
                    $langs[$code] + array(
                    'lang' => $code,
                    'locale' => $locale,
                    'path' => $f,
                    'phar' => substr($f, -5) == '.phar',
                    'code' => $base,
                );
            }
        }
        uasort($installed, function($a, $b) { return strcasecmp($a['code'], $b['code']); });

        return $cache = $installed;
    }

    // TODO: Move this to the REQUEST class or some middleware when that
    // exists.
    // Algorithm borrowed from Drupal 7 (locale.inc)
    static function getDefaultLanguage() {
        global $cfg;

        if (empty($_SERVER["HTTP_ACCEPT_LANGUAGE"]))
            return $cfg->getSystemLanguage();

        $languages = self::availableLanguages();

        // The Accept-Language header contains information about the
        // language preferences configured in the user's browser / operating
        // system. RFC 2616 (section 14.4) defines the Accept-Language
        // header as follows:
        //   Accept-Language = "Accept-Language" ":"
        //                  1#( language-range [ ";" "q" "=" qvalue ] )
        //   language-range  = ( ( 1*8ALPHA *( "-" 1*8ALPHA ) ) | "*" )
        // Samples: "hu, en-us;q=0.66, en;q=0.33", "hu,en-us;q=0.5"
        $browser_langcodes = array();
        $matches = array();
        if (preg_match_all('@(?<=[, ]|^)([a-zA-Z-]+|\*)(?:;q=([0-9.]+))?(?:$|\s*,\s*)@',
            trim($_SERVER['HTTP_ACCEPT_LANGUAGE']), $matches, PREG_SET_ORDER)) {
          foreach ($matches as $match) {
            // We can safely use strtolower() here, tags are ASCII.
            // RFC2616 mandates that the decimal part is no more than three
            // digits, so we multiply the qvalue by 1000 to avoid floating
            // point comparisons.
            $langcode = strtolower($match[1]);
            $qvalue = isset($match[2]) ? (float) $match[2] : 1;
            $browser_langcodes[$langcode] = (int) ($qvalue * 1000);
          }
        }

        // We should take pristine values from the HTTP headers, but
        // Internet Explorer from version 7 sends only specific language
        // tags (eg. fr-CA) without the corresponding generic tag (fr)
        // unless explicitly configured. In that case, we assume that the
        // lowest value of the specific tags is the value of the generic
        // language to be as close to the HTTP 1.1 spec as possible.
        //
        // References:
        // http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4
        // http://blogs.msdn.com/b/ie/archive/2006/10/17/accept-language-header-for-internet-explorer-7.aspx
        asort($browser_langcodes);
        foreach ($browser_langcodes as $langcode => $qvalue) {
          $generic_tag = strtok($langcode, '-');
          if (!isset($browser_langcodes[$generic_tag])) {
            $browser_langcodes[$generic_tag] = $qvalue;
          }
        }

        // Find the enabled language with the greatest qvalue, following the rules
        // of RFC 2616 (section 14.4). If several languages have the same qvalue,
        // prefer the one with the greatest weight.
        $best_match_langcode = FALSE;
        $max_qvalue = 0;
        foreach ($languages as $langcode => $language) {
          // Language tags are case insensitive (RFC2616, sec 3.10).
          // We use _ as the location separator
          $langcode = str_replace('_','-',strtolower($langcode));

          // If nothing matches below, the default qvalue is the one of the wildcard
          // language, if set, or is 0 (which will never match).
          $qvalue = isset($browser_langcodes['*']) ? $browser_langcodes['*'] : 0;

          // Find the longest possible prefix of the browser-supplied language
          // ('the language-range') that matches this site language ('the language tag').
          $prefix = $langcode;
          do {
            if (isset($browser_langcodes[$prefix])) {
              $qvalue = $browser_langcodes[$prefix];
              break;
            }
          } while ($prefix = substr($prefix, 0, strrpos($prefix, '-')));

          // Find the best match.
          if ($qvalue > $max_qvalue) {
            $best_match_langcode = $language['code'];
            $max_qvalue = $qvalue;
          }
        }

        return $best_match_langcode;
    }

    static function getCurrentLanguage($user=false) {
        global $thisstaff, $thisclient;

        $user = $user ?: $thisstaff ?: $thisclient;
        if ($user && method_exists($user, 'getLanguage'))
            return $user->getLanguage();
        if (isset($_SESSION['client:lang']))
            return $_SESSION['client:lang'];
        return self::getDefaultLanguage();
    }

    static function getTtfFonts() {
        if (!class_exists('Phar'))
            return;
        $fonts = $subs = array();
        foreach (self::availableLanguages() as $code=>$info) {
            if (!$info['phar'] || !isset($info['fonts']))
                continue;
            foreach ($info['fonts'] as $simple => $collection) {
                foreach ($collection as $type => $name) {
                    list($name, $url) = $name;
                    $ttffile = 'phar://' . $info['path'] . '/fonts/' . $name;
                    if (file_exists($ttffile))
                        $fonts[$simple][$type] = $ttffile;
                }
                if (@$collection[':sub'])
                    $subs[] = $simple;
            }
        }
        $rv = array($fonts, $subs);
        Signal::send('config.ttfonts', null, $rv);
        return $rv;
    }

    static function bootstrap() {

        require_once INCLUDE_DIR . 'class.translation.php';

        $domain = 'messages';
        TextDomain::setDefaultDomain($domain);
        TextDomain::lookup()->setPath(I18N_DIR);

        // User-specific translations
        function _N($msgid, $plural, $n) {
            return TextDomain::lookup()->getTranslation()
                ->ngettext($msgid, $plural, is_numeric($n) ? $n : 1);
        }

        // System-specific translations
        function _S($msgid) {
            global $cfg;
            return __($msgid);
        }
        function _NS($msgid, $plural, $count) {
            global $cfg;
        }

        // Phrases with separate contexts
        function _P($context, $msgid) {
            return TextDomain::lookup()->getTranslation()
                ->pgettext($context, $msgid);
        }
        function _NP($context, $singular, $plural, $n) {
            return TextDomain::lookup()->getTranslation()
                ->npgettext($context, $singular, $plural, is_numeric($n) ? $n : 1);
        }

        // Language-specific translations
        function _L($msgid, $locale) {
            return TextDomain::lookup()->getTranslation($locale)
                ->translate($msgid);
        }
        function _NL($msgid, $plural, $n, $locale) {
            return TextDomain::lookup()->getTranslation($locale)
                ->ngettext($msgid, $plural, is_numeric($n) ? $n : 1);
        }
    }
}