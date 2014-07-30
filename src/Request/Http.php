<?php

namespace Phlite\Request;

class Http {

    function redirect($url,$delay=0,$msg='') {

        $iis = strpos($_SERVER['SERVER_SOFTWARE'], 'IIS') !== false;
        @list($name, $version) = explode('/', $_SERVER['SERVER_SOFTWARE']);
        // Legacy code for older versions of IIS that would not emit the
        // correct HTTP status and headers when using the `Location`
        // header alone
        if ($iis && version_compare($version, '7.0', '<')) {
            header("Refresh: $delay; URL=$url");
        }else{
            header("Location: $url");
        }
        print('<html></html>');
        flush();
        ob_flush();
        exit;
    }

    function cacheable($etag, $modified, $ttl=3600) {
        // Thanks, http://stackoverflow.com/a/1583753/1025836
        $last_modified = Misc::db2gmtime($modified);
        header("Last-Modified: ".date('D, d M y H:i:s', $last_modified)." GMT", false);
        header('ETag: "'.$etag.'"');
        header("Cache-Control: private, max-age=$ttl");
        header('Expires: ' . gmdate(DATE_RFC822, time() + $ttl)." GMT");
        header('Pragma: private');
        if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $last_modified ||
            @trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
                header("HTTP/1.1 304 Not Modified");
                exit();
        }
    }

    /**
     * Creates the filename=... part of the Content-Disposition header. This
     * is browser dependent, so the user agent is inspected to determine the
     * best encoding format for the filename
     */
    function getDispositionFilename($filename) {
        $user_agent = strtolower ($_SERVER['HTTP_USER_AGENT']);
        if (false !== strpos($user_agent,'msie')
                && false !== strpos($user_agent,'win'))
            return 'filename='.rawurlencode($filename);
        elseif (false !== strpos($user_agent, 'safari')
                && false === strpos($user_agent, 'chrome'))
            // Safari and Safari only can handle the filename as is
            return 'filename='.str_replace(',', '', $filename);
        else
            // Use RFC5987
            return "filename*=UTF-8''".rawurlencode($filename);
    }

    function download($filename, $type, $data=null, $disposition='attachment') {
        header('Pragma: private');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private');
        header('Content-Type: '.$type);
        header(sprintf('Content-Disposition: %s; %s',
            $disposition,
            self::getDispositionFilename(basename($filename))));
        header('Content-Transfer-Encoding: binary');
        if ($data !== null) {
            header('Content-Length: '.strlen($data));
            print $data;
            exit;
        }
    }
}
