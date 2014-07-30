<?php

namespace Phlite\Request;

class Request {

    // Encoding of the request, usually indicated by the remote client in
    // the Content-Type header
    var $encoding;

    /**
     * Returns TRUE if the request is being serviced over an encrypted
     * connection.
     */
    function isSecure() {
        return (isset($_SERVER['HTTPS'])
                && strtolower($_SERVER['HTTPS']) == 'on')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
                && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https');
    }

    /**
     * Retrieve the remote client address. This is usually REMOTE_ADDR but
     * can be something else based on the server software configuration.
     */
    function getRemoteAddress() {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Take the left-most item for X-Forwarded-For
            return trim(array_pop(
                explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])));
        }
        else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
}
