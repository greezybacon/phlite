<?php

namespace Phlite;

use Phlite\Project\Settings;
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
    static $current_project = null;

    protected $settings;
    protected $applications = array();
    protected $current_app;
    
    protected $_tcps;

    function __construct($settings=false) {
        $this->settings = new $this::$global_settings();
        if ($settings)
            $this->loadSettings($settings);
		spl_autoload_register(array($this, '_autoload'));
        if (!isset($this::$current_project))
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

	function _autoload($class) {
		$path = explode('\\', $class);
		$path = implode('/', $path) . '.php';
        return (@include $path);
	}

    // -------- SETTINGS ----------------------------
    function getSettings() {
        return $this->settings;
    }

    function loadSettings($filename=false) {
        $this->settings->loadFile($filename, $this);
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
    function getMiddleware() {
        return array(
            'Phlite\Request\Middleware\SessionMiddleware',
            'Phlite\Request\Middleware\AuthMiddleware',
            'Phlite\Request\Middleware\CsrfMiddleware',
        );
    }
    
    function getUrls() {
        return $this->getSettings()->get('URLS') ?: (include 'urls.php');
    }

    static function getCurrent() {
        return self::$current_project;
    }
    
    // Allow static calls as singleton calls
    static function __callStatic($what, $how) {
        return call_user_func_array(static::getCurrent(), $how);
    }
}
