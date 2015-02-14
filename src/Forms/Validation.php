<?php

namespace Phlite\Forms;

class Validation {
    
    static function isIp($ip) {
        if (!$ip or empty($ip))
            return false;

        $ip = trim($ip);
        # Thanks to http://stackoverflow.com/a/1934546
        if (function_exists('inet_pton')) { # PHP 5.1.0
            # Let the built-in library parse the IP address
            return @inet_pton($ip) !== false;
        } else if (preg_match(
            '/^(?>(?>([a-f0-9]{1,4})(?>:(?1)){7}|(?!(?:.*[a-f0-9](?>:|$)){7,})'
            .'((?1)(?>:(?1)){0,5})?::(?2)?)|(?>(?>(?1)(?>:(?1)){5}:|(?!(?:.*[a-f0-9]:){5,})'
            .'(?3)?::(?>((?1)(?>:(?1)){0,3}):)?)?(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])'
            .'(?>\.(?4)){3}))$/iD', $ip)) {
            return true;
        }
        return false;
    }
}