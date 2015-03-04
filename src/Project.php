<?php

namespace Phlite;

use Phlite\Db;
use Phlite\Dipatch;
use Phlite\Project\Settings;
use Phlite\Request\MiddlewareList;
use Phlite\Signal;
use Phlite\Util;

/**
 * Class: Project
 *
 * The project is the top-level equipment for Phlite project. It contains
 * the root information for all the applications installed
 */
class Project {

    var $settings_file = '';
    
    static $global_settings = 'Phlite\Project\DefaultSettings';
    private static $current_project;

    protected $settings;
    protected $applications = array();
    protected $current_app;
    
    protected $_tcps;

    function __construct($settings=false) {
        $this->settings = new static::$global_settings();
        if ($settings)
            $this->loadSettings($settings);
		spl_autoload_register(array($this, '_autoload'));
        if (!isset(self::$current_project))
            self::$current_project = $this;
    }

    /**
     * Function: getApplications
     *
     * Used by the project to fetch the list of applications. The
     * applications list can be defined in the settings file or this
     * function can be overridden to return the list directly
     */
    function getApplications() {
        if (!$this->applications) {
            foreach ($this->settings->get('APPLICATIONS') as $app_class) {
                $app = new $app_class($this);
                $this->applications[$app->getNamespace()] = $app;
            }
        }
        return $this->applications;
    }

    function getCurrentApp() {
        return $this->current_app;
    }
    function setCurrentApp(Project\Application $app) {
        $this->current_app = $app;
    }

	function _autoload($class_name, $exts) {
        foreach ($this->applications as $namespace=>$app) {
            if (strpos($class_name, $namespace) !== 0) {
                continue;

            $file = str_replace(["$namespace\\", '\\'], ['', DIRECTORY_SEPARATOR],
                 $class_name);
            $base = $this->getFilesystemRoot() . DIRECTORY_SEPARATOR . $file;
            foreach ($exts as $X) {
                if (is_file($file.$X))
                    require_once $file.$X;
            }
        }
	}
    
    function startup() {
        foreach ($this->getSettings()->get('DATABASES') as $name=>$info) {
            Db\Manager::addConnection($info, $name);
        }
    }

    // -------- SETTINGS ----------------------------
    function getSettings() {
        return $this->settings;
    }
    function getSetting($key, $default=null) {
        return $this->getSettings()->get($key, $default);
    }

    function loadSettings($filename=false) {
        $this->settings->loadFile($filename);
    }

    // -------- DATABASE ----------------------------
    function getDatabase($name='default') {
    }

    // -------- TEMPLATE ----------------------------
    function getTemplateContexts() {
        if (!isset($this->_tcps)) {
            $this->_tcps = new Util\ListObject(
                $this->settings->get('TEMPLATE_CONTEXT_PROCESSORS', []));
            foreach ($this->getApplications() as $A) {
                $this->_tcps->extend($A->getTemplateContexts() ?: []);
            }
        }
        return $this->_tcps;
    }

    function getTemplateEngine() {
    }

    /**
     * Used to retrieve a list of middleware enabled for the project.
     */
    protected $middleware;
    function getMiddleware() {
        if (!isset($this->middleware)) {
            $this->middleware = new MiddlewareList();
            foreach ($this->getSetting('MIDDLEWARE_CLASSES', []) as $c) {
                $this->middleware[] = new $c();
            }
            foreach ($this->getApplications() as $app) {
                if ($mw = $app->getMiddleware()) {
                    foreach ($mw as $c) {
                        $this->middleware[] = new $c();
                    }
                }
            }
        }
        return $this->middleware;
    }
    
    function getDispatcher() {
        if ($urls = $this->getUrls())
            return new Dispatch\RegexDispatcher($urls);
        if ($root = $this->getDispatchRoot())
            return new Dispatch\MethodDispatcher($root);
    }
    
    function getUrls() {
        $urls = $this->getSettings()->get('URLS');
        if ($urls)
            return (include $urls); 
    }
    
    function getDispatchRoot() {
        return null;
    }

    static function getCurrent() {
        return self::$current_project;
    }
    
    var $root;
    function getFilesystemRoot() {
        if (!isset($this->root)) {
            $RC = new \ReflectionClass($this);
            $this->root = dirname($RC->getFilename());
        }
        return $this->root;
    }
    
    // Allow static calls as singleton calls
    static function __callStatic($what, $how) {
        return call_user_func_array(self::getCurrent(), $how);
    }
}