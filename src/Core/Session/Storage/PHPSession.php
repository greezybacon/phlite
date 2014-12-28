<?php

namespace Phlite\Core\Session\Storage;

use Phlite\Core\Session\SessionBase;

/**
 * This is a really boring storage backend which simply connects the
 * builtin PHP session engine.
 */
class PHPSession
extends SessionBase {
    
    // Fallback settings
    static $lifetime = 86400;
    static $session_name = 'PHLITE_SESS';
    
    function exists($id) {
        // ??
        return false;
    }
    
    function delete($id) {
        // Not necessary
    }
    
    function save($must_create=false) {
        session_commit();
    }
    
    function load() {
        $settings = Project::getSettings();
        
        // Set session cleanup time to match TTL
        $ttl = $ttl 
            ?: $settings->get('SESSION_IDLE')
            ?: ini_get('session.gc_maxlifetime')
            ?: static::$lifetime
        ini_set('session.gc_maxlifetime', $ttl);
        
        // Set specific session name.
        session_name($settings->get('SESSION_NAME') ?: static::$session_name);
        session_id($this->session_key);
        
        // Cookie is sent by the middleware
        ini_set('session.use_only_cookies', 1);
        ini_set('session.use_cookies', 0);
            
        session_start();
        return $_SESSION;
    }
}