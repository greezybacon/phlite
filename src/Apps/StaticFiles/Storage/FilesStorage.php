<?php

namespace Phlite\Apps\StaticFiles\Storage;

use Phlite\Request;

class FilesStorage extends BaseStorage {
    
    function serve($path, $base) {
        if (!($fp = fopen($path, 'rb')))
            return false;
        
        $response = new Request\StreamResponse($fp);
        $response->cacheable(md5_file($path), filemtime($path));
        
        if (class_exists('finfo')) {
            $finfo = new \finfo(FILEINFO_MIME);
            $type = $finfo->file($path);
            $response->setType($type);
        }

        return $response;
    }
    
    function store($path, $base) {
        // Pass.
    }
}