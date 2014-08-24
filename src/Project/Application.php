<?php

namespace Phlite\Project;

use Phlite\Dispatch\Dispatcher;

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

    var $root;
    
    // Label for the application. Used as a prefix for objects related to
    // this application such as database table names, where duplicates
    // might exist between applications. This is automatically inspected
    // if not defined here.
    var $label;
    
    var $namespace;
    
    function __construct($root=false) {
        $root = $root ?: dirname(__file__);
    }

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
    function getUrls() {
        if ($this->root . '/urls.inc')
            return (include $this->root . '/urls.inc');
    }
    
    function resolve($url, $args=null) {
        $disp = new Dispatcher($this->getUrls());
        return $disp->resolve($url, $args);
    }
    
    /**
     * Returs a list of models used by this application. Listing them here
     * is necessary because of the inability of PHP to fetch a list of all
     * classes in a namespace or all subclasses of a particular class. This
     * listing is of greatest use when syncing the database schema defined
     * in the application to the database backend.
     */
    function getModels() {
        // TODO: Include all the files in {$root}/Db as a convenience, 
        //       assuming that, by example, models for this application are
        //       defined in a relative Db\ namespace
        
        $result = array();
        $namespace = $this->getFullNamespace();
        // Fetch a list of all subclasses of Phlite\Db\Model in this 
        // application's namespace
        foreach (get_declared_classes() as $class) {
            if (is_subclass_of($class, 'Phlite\Db\Model')
                    && strpos($class, $namepace) === 0)
                $result[] = $class;
        }
        return $result;
    }

    /**
     * Determines the root filesystem path of this application.
     */
    function getFilesystemRoot() {
        return $this->root;
    }

    /**
     * Function: getTemplateFolder
     *
     * Returns the name of a folder which contains templates specific to
     * this application.
     */
    function getTemplateFolder() {
        return 'Templates';
    }
    
    function getFullNamespace() {
        // Build namespace of this application by removing the classname 
        // from the full namespaced classname
        if (!isset($this->namesapce)) {
            $namespace = explode('\\', get_class($this));
            array_pop($namespace);
            $this->namespace = implode('\\', $namespace);
        }
        return $this->namespace;
    }
    
    // Short name of the app for database tables and templates. This is
    // usally inspected from the php namespace of the Application subclass
    function getLabel() {
        if (!isset($this->label)) {
            $namespace = explode('\\', $this->getFullNamespace());
            $this->label = array_pop($namespace);
        }
        return $this->label;
    }

    function getStaticFolder() {
        return $this->root . '/Static';
    }

    function getI18nFolder() {
        return $this->root . 'I18n';
    }
}
