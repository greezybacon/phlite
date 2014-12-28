<?php

namespace Phlite\Text\Io;

class Rfc2397 {
    /**
     * Parse RFC 2397 formatted data strings. Format according to the RFC
     * should look something like:
     *
     * data:[type/subtype][;charset=utf-8][;base64],data[#filename]
     *
     * Parameters:
     * $data - (string) RFC2397 formatted data string
     * $output_encoding - (string:optional) Character set the input data
     *      should be encoded to.
     * $always_convert - (bool|default:true) If the input data string does
     *      not specify an input encding, assume iso-8859-1. If this flag is
     *      set, the output will always be transcoded to the declared
     *      output_encoding, if set.
     *
     * Returs:
     * array (data=>parsed and transcoded data string, type=>MIME type
     * declared in the data string or text/plain otherwise)
     *
     * References:
     * http://www.ietf.org/rfc/rfc2397.txt
     */
    static function parse($data, $output_encoding=false, $always_convert=true) {
        if (substr($data, 0, 5) != "data:")
            return array('data'=>$data, 'type'=>'text/plain');

        $data = substr($data, 5);
        list($meta, $contents) = explode(",", $data, 2);
        if ($meta)
            list($type, $extra) = explode(";", $meta, 2);
        else
            $extra = '';
        if (!isset($type) || !$type)
            $type = 'text/plain';

        $parameters = explode(";", $extra);

        # Handle 'charset' hint in $extra, such as
        # data:text/plain;charset=iso-8859-1,Blah
        # Convert to utf-8 since it's the encoding scheme for the database.
        $charset = ($always_convert) ? 'iso-8859-1' : false;
        foreach ($parameters as $p) {
            list($param, $value) = explode('=', $extra);
            if ($param == 'charset')
                $charset = $value;
            elseif ($param == 'base64')
                $contents = base64_decode($contents);
        }
        if ($output_encoding && $charset)
            $contents = Format::encode($contents, $charset, $output_encoding);

        return array(
            'data' => $contents,
            'type' => $type
        );
    }
}
