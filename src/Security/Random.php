<?php

namespace Phlite\Security;

class Random {
    
    static function getRandomText($len, $chars=false) {
        $chars = $chars ?: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890_=';
        
        // Determine the number of bits we need
        $char_count = strlen($chars);
        $bits_per_char = ceil(log($char_count, 2));
        $bytes = ceil(4 * $len / floor(32 / $bits_per_char));
        // Pad to 4 byte boundary
        $bytes += (4 - ($bytes % 4)) % 4;
        
        // Fetch some random data blocks
        $data = static::getRandomBytes($bytes);

        $mask = (1 << $bits_per_char) - 1;
        $loops = (int) (32 / $bits_per_char);
        $output = '';
        $numbers = unpack('V*', $data);
        array_shift($numbers);
        foreach ($numbers as $int) {
            for ($i = $loops; $i > 0; $i--) {
                $output .= $chars[($int & $mask) % $char_count];
                $int >>= $bits_per_char;
            }
        }
        return substr($output, 0, $len);
    }
    
    static function getRandomBytes($len) {
        // XXX: Move this to a utility class
        $windows = !strncasecmp(PHP_OS, 'WIN', 3);
        
        if ($windows) {
            if (function_exists('openssl_random_pseudo_bytes')
                    && version_compare(PHP_VERSION, '5.3.4', '>='))
                return openssl_random_pseudo_bytes($len);

            // Looks like mcrypt_create_iv with MCRYPT_DEV_RANDOM is still
            // unreliable on 5.3.6:
            // https://bugs.php.net/bug.php?id=52523
            if (function_exists('mcrypt_create_iv')
                    && version_compare(PHP_VERSION, '5.3.7', '>='))
                return mcrypt_create_iv($len);
        } 
        else {
            if (function_exists('openssl_random_pseudo_bytes'))
                return openssl_random_pseudo_bytes($len);

            static $fp = null;
            if ($fp == null)
                $fp = @fopen('/dev/urandom', 'rb');

            if ($fp)
                return fread($fp, $len);

            if (function_exists('mcrypt_create_iv'))
                return mcrypt_create_iv($len, MCRYPT_DEV_URANDOM);
        }

        $request = Request::getCurrent();
        $seed = microtime() . getmypid();
        if ($request)
            $seed .= $request->getSettings()->get('SECRET_SALT');
        if ($request && $request->session)
            $seed .= $request->session->getSessionKey();
        
        $start = mt_rand(5, PHP_INT_MAX);
        $output = '';
        for ($i=$start; strlen($output) < $len; $i++)
            $output .= hash_hmac('sha1', $i, $seed, true);

        return substr($output, 0, $len);
    }
}