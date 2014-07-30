<?php

namespace Phlite\Project;

/**
 * Class: Application
 *
 * Application are the major component of a Phlite project. Applications
 * serve as a container for all the sub features such as Url routing,
 * template lookups and environment, static content and media, etc. They do
 * not, however, contain settings such as database connection information.
 * Such information is placed in settings modules and is connected to the
 * root project.
 */
abstract class Application {

    /**
     * Function: getUrls
     *
     * Fetches a list of URLs to be connected with a dispatcher. The URLs
     * will be used to connect the HTTP request with a view inside this
     * application
     *
     * Returns:
     * (array<Phlite\Dispatcher\Route>) a list of objects used by the
     * dispatcher to connect the Http request with a view inside this
     * applicatoin.
     *
     * Example:
     * return array(
     *    Route('/foo/bar', 'Apps\MyApp\Views\FooBar'),
     * );
     */
    abstract function getUrls();

    /**
     * Function: getTemplateFolder
     *
     * Returns the name of a folder which contains templates specific to
     * this application.
     */
    function getTemplateFolder() {
        return 'Templates';
    }

    function getStaticFolder() {
        return 'Static';
    }

    function getI18nFolder() {
        return 'I18n';
    }
}
