<?php

namespace Phlite\Project;

use Phlite\Dispatch\Dispatcher;
use Phlite\Dispatch\Route;
use Phlite\Project;



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
    var $urls_path = 'urls.inc';

    function __construct($project=false) {
        $RC = new \ReflectionClass($this);
        $this->root = dirname($RC->getFilename());
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
     * application.
     *
     * Example:
     * return array(
     *    Route('/foo/bar', 'Apps\MyApp\Views\FooBar'),
     * );
     */
    function getUrls() {
        if ($this->root . '/' . $this->urls_path)
            return (include $this->root . '/' . $this->urls_path);
    }
    
    function resolve($url, $args=null, $setCurrentProject=true) {
        $disp = new Dispatcher($this->getUrls());
        if ($setCurrentProject) {
            Project::getCurrent()->setCurrentApp($this);
        }
        return $disp->resolve($url, $args);
    }

    function getNamespace() {
        $class = get_class($this);
        $namespace = explode('\\', $class);
        array_pop($namespace);
        return implode('\\', $namespace);
    }
    function getName() {
        $parts = explode('\\', $this->getNamespace());
        return strtolower(array_pop($parts));
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
        // XXX: fallback to project settings TEMPLATE_DIRS
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
        return $this->root . '/I18n';
    }
    
    static function asRoute($url) {
        return new Route($url, get_called_class(), null, Route::APPLICATION);
    }
    
    /**
     * getTemplateContexts
     *
     * Simple interface for applications to specify their own template
     * contexts, which is mostly useful for internal applications to specify
     * contests without the need to modify the project settings.
     */
    function getTemplateContexts() {
        return false;
    }
    
    // ------ CLI interfaces -----------------
    function getCliModules() {
        $app_mods = array();
        
        if (!is_dir($this->root . '/Cli'))
            return $app_mods;
        
        $mods = new \FilesystemIterator($this->root . '/Cli',
            \FilesystemIterator::UNIX_PATHS | \FilesystemIterator::SKIP_DOTS);
        foreach ($mods as $M) {
            if (substr($M, -4) != '.php')
                continue;            
            
            require_once($M->getRealPath());
            $className = $this->getFullNamespace() . '\\Cli\\'
                . $M->getBasename('.php');
            $module = new $className();
            $app_mods[$module->getName()] = $module;
        }
        return $app_mods;
    }
}
