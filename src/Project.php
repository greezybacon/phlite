<?php

namespace Phlite;

/**
 * Class: Project
 *
 * The project is the top-level equipment for Phlite project. It contains
 * the root information for all the applications installed
 */
abstract class Project {

    var $settings_file = '';

    private $settings;

    function __construct() {
        $this->settings = new Settings();
        $this->loadSettings();
    }

    /**
     * Function: getApplications
     *
     * Used by the project to fetch the list of applications. The
     * applications list can be defined in the settings file or this
     * function can be overridden to return the list directly
     */
    function getApplications() {
        return $this->getSettings()->get('applications');
    }

    // -------- SETTINGS ----------------------------
    function getSettings() {
        return $this->settings;
    }

    function loadSettings($filename=false) {
    }

    // -------- DATABASE ----------------------------
    function getDatabase($name='default') {
    }

    // -------- TEMPLATE ----------------------------
    function getTemplateContexts() {
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
    
}
