<?php

namespace Phlite\Core\Session\Storage;

interface StorageBackend {
    function write($id, $data);
    function read($id);
    
    function setName($name);
    function start();
}