<?php

namespace Phlite\Core\Session;

use Phlite\Project;
use Phlite\Request\Request;
use Phlite\Security\Random;
use Phlite\Util\Dict;

/**
 * Session
 *
 * Simple wrapper around the built-in PHP session equipment meant in 
 * providing more consistent, object-orienged access to a user's
 * session. It also provides extensibility for easier subclassing
 * in Phlite projects. It also avoids access to a global variable which
 * makes testing code significantly easier.
 */
abstract class SessionBase
implements \Iterator, \ArrayAccess, \Serializable, \Countable {
    
    static $VALID_KEY_CHARS = 'abcdefghijklmnopqrstuvwxyz0123456789';
    static $idle_time = 30;
    
    // ArrayObject like interface ----------------------
    protected $storage;

    function clear() {
        $this->storage = array();
        $this->accessed = true;
        $this->modified = true;
    }

    function copy() {
        return clone $this;
    }

    function keys() {
        $this->lazyLoad();
        return array_keys($this->storage);
    }

    function pop($key, $default=null) {
        $this->lazyLoad();
        if (isset($this->storage[$key])) {
            $rv = $this->storage[$key];
            unset($this->storage[$key]);
            $this->modified = true;
            return $rv;
        }
        return $defaut;
    }

    function setDefault($key, $default=false) {
        $this->lazyLoad();
        if (!isset($this[$key])) {
            $this->modified = true;
            $this[$key] = $default;
        }
        return $this[$key];
    }

    function get($key, $default=null) {
        $this->lazyLoad();
        if (isset($this->storage[$key]))
            return $this->storage[$key];
        else
            return $default;
    }

    function update(/* Iterable */ $other) {
        $this->lazyLoad();
        foreach ($other as $k=>$v)
            $this[$k] = $v;
        $this->modified = true;
    }

    function values() {
        $this->lazyLoad();
        return array_values($this->storage);
    }

    // Countable
    function count() { 
        $this->lazyLoad();
        return count($this->storage); 
    }

    // Iterator
    function current() { return current($this->storage); }
    function key() { return current($this->storage); }
    function next() { return next($this->storage); }
    function rewind() { return reset($this->storage); }
    function valid() { return null != key($this->storage); }

    // ArrayAccess
    function offsetExists($offset) { 
        $this->lazyLoad();
        return isset($this->storage[$offset]); 
    }
    function offsetGet($offset) {
        $this->lazyLoad();
        if (!isset($this->storage[$offset]))
            throw new \OutOfBoundsException();
        return $this->storage[$offset];
    }
    function offsetSet($key, $value) {
        $this->lazyLoad();
        $this->modified = true;
        $this->storage[$key] = $value;
    }
    function offsetUnset($offset) {
        $this->lazyLoad();
        $this->modified = true;
        unset($this->storage[$offset]);
    }

    // Serializable
    protected function hash($what) {
        return hash_hmac('sha1', $what, get_class($this));
    }
    function serialize() { 
        $data = serialize($this->storage); 
        return $this->hash($data) .':'. $data;
    }
    function unserialize($what) {
        list($hash, $data) = explode(':', $what, 2);
        // Use constant time comparison
        if ($hash != $this->hash($data))
            throw new Exception\SuspiciousSession('Corrupted session data');
        $this->storage = unserialize($data);
    }

    function __toString() {
        foreach ($this->storage as $key=>$v) {
            $items[] = (string) $key . '=> ' . (string) $value;
        }
        return '{'.implode(', ', $items).'}';
    }

    function asArray() {
        $this->lazyLoad();
        return $this->storage;
    }
    
    // Session magic functions --------------------------------
    protected $session_id;
    protected $accessed = false;
    protected $modified = false;
    
    function __construct($session_id=null) {
        $this->session_id = $session_id;
    }
    
    function getSessionId() {
        return $this->session_id;
    }
    
    protected function getNewSessionKey() {
        while (true) {
            $key = Random::getRandomText(32, self::$VALID_KEY_CHARS);
            if (!self::exists($key))
                break;
        }
        return $key;
    }
    
    protected function getOrCreateSessionKey() {
        if (!isset($this->session_id)) {
            $this->session_id = $this->getNewSessionKey();
        }
        return $this->session_id;
    }
    
    protected function lazyLoad($noLoad=false) {
        $this->accessed = true;
        if (!isset($this->storage)) {
            if (!isset($this->session_id) || $noLoad) {
                $this->storage = array();
            }
            else {
                $this->storage = $this->load();
                $this->internal = $this->storage->get(':i', null);
                if (!$this->internal) {
                    $this->setupInternal();
                }
            }
        }
    }
    
    function getSessionKey() {
        return session_id();
    }
    
    function getExpiryTime() {
        $settings = Project::getCurrent()->getSettings();
        return time() + ($settings->get('SESSION_IDLE',
            ini_get('session.gc_maxlifetime')
            ?: static::$lifetime));
    }
    
    protected function setupInternal() {
        $this->internal = array();
        $settings = Project::getCurrent()->getSettings();
    
        // 1. Remote address (for IP binding)
        $bind_ip = isset($bind_ip) ? $bind_ip
            : (isset($settings['SESSION_BIND_IP']) ? $settings['SESSION_BIND_IP']
                : static::$bind_ip);
        if ($bind_ip) {
            $this->internal['ip'] = $request->getRemoteAddress();
        }
        
        // 2. If the current request is HTTPS, so the session can be
        //    checked later for validity
        $this->internal['https'] = $request->isHttps();
    }
    
    function isValid() {
        $settings = Request::getCurrent()->getSettings();

        // 1. Check for consistent remote address (for IP binding)
        if (isset($this->internal['ip'])
            && $this->internal['ip'] != $request->getRemoteAddress()
        ) {
            throw new Exception\InvalidSession();
        }
            
        // 2. If the current request is HTTPS and if that differs from what
        //    the session was created for
        if (isset($this->internal['https'])
            && $this->internal['https'] != $request->isHttps()
        ) {
            throw new Exception\InvalidSession();
        }

        // 3. Check for idle timeout
        $deadband = $settings->get('SESSION_IDLE', static::$idle_time);
        if (isset($this->internal['idle'])
            && time() - $this->internal['idle'] > $deadband
        ) {
            throw new Exception\IdleTimeout();
        }
    }
    
    function isEmpty() {
        return !isset($this->session_id) && !isset($this->storage);
    }
    
    function isModified() {
        return $this->modified;
    }
    
    // Backend specific methods -------------------------------
    
    abstract function exists($session_id);
    
    abstract function create();
    
    abstract function save($must_create=false);
    
    abstract function delete($session_id);
    
    abstract function load();
    
    static function clear_expired() {
        // To default implementation does nothing. If this is also not
        // necessary by a backend, it is not necessary to override it.
    }
}
