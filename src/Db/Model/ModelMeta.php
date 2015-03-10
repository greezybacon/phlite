<?php

namespace Phlite\Db\Model;

use Phlite\Db\Exception;
use Phlite\Db\DbEngine;

/**
 * Meta information about a model including edges (relationships), table
 * name, default sorting information, database fields, etc.
 *
 * This class is constructed and built automatically from the model's
 * ::_inspect method using a class's ::$meta array.
 */
class ModelMeta implements \ArrayAccess {

    static $defaults = array(
        'pk' => false,
        'table' => false,
        'form' => false,
        'defer' => array(),
        'select_related' => array(),
        'view' => false,
    );

    var $base;
    var $model;

    function __construct($model) {
        $this->model = $model;
        $meta = $model::$meta + self::$defaults;

        // TODO: Merge ModelMeta from parent model (if inherited)

        if (!$meta['table'])
            throw new Exception\ModelConfigurationError(
                sprintf('%s: Model does not define meta.table', $model));
        elseif (!$meta['pk'])
            throw new Exception\ModelConfigurationError(
                sprintf('%s: Model does not define meta.pk', $model));

        // Ensure other supported fields are set and are arrays
        foreach (array('pk', 'ordering', 'defer') as $f) {
            if (!isset($meta[$f]))
                $meta[$f] = array();
            elseif (!is_array($meta[$f]))
                $meta[$f] = array($meta[$f]);
        }

        // Break down foreign-key metadata
        if (!isset($meta['joins']))
            $meta['joins'] = array();
        foreach ($meta['joins'] as $field => &$j) {
            $j = $this->processJoin($j);
        }
        unset($j);
        $this->base = $meta;
    }
    
    function processJoin(&$j) {
        if (isset($j['reverse'])) {
            list($fmodel, $key) = explode('.', $j['reverse']);
            if (strpos($fmodel, '\\') === false) {
                // Transfer namespace from this model
                $fmodel = $this['namespace']. '\\' . $fmodel;
            }
            $info = $fmodel::$meta['joins'][$key];
            $constraint = array();
            if (!is_array($info['constraint']))
                throw new Exception\ModelConfigurationError(sprintf(
                    // `reverse` here is the reverse of an ORM relationship
                    '%s: Reverse does not specify any constraints',
                    $j['reverse']));
            foreach ($info['constraint'] as $foreign => $local) {
                list(,$field) = explode('.', $local);
                $constraint[$field ?: $local] = "$fmodel.$foreign";
            }
            $j['constraint'] = $constraint;
            if (!isset($j['list']))
                $j['list'] = true;
            if (!isset($j['null']))
                // By default, reverse releationships can be empty lists
                $j['null'] = true;
        }
        // XXX: Make this better (ie. composite keys)
        foreach ($j['constraint'] as $local => $foreign) {
            list($class, $field) = explode('.', $foreign);
            if (strpos($class, '\\') === false) {
                // Transfer namespace from this model
                $class = $this['namespace']. '\\' . $class;
                $j['constraint'][$local] = "$class.$field";
            }
            if ($local[0] == "'" || $field[0] == "'" || !class_exists($class))
                continue;
            $j['fkey'] = array($class, $field);
            $j['local'] = $local;
        }
        return $j;
    }

    function offsetGet($field) {
        if (!isset($this->base[$field]))
            $this->setupLazy($field);
        return $this->base[$field];
    }
    function offsetSet($field, $what) {
        $this->base[$field] = $what;
    }
    function offsetExists($field) {
        return isset($this->base[$field]);
    }
    function offsetUnset($field) {
        throw new \Exception('Model MetaData is immutable');
    }

    function setupLazy($what) {
        switch ($what) {
        case 'fields':
            $this->base['fields'] = self::inspectFields();
            break;
        case 'namespace':
            $namespace = explode('\\', $this->model);
            array_pop($namespace);
            $this->base['namespace'] = implode('\\', $namespace);
            break;
        default:
            throw new \Exception($what . ': No such meta-data');
        }
    }

    function inspectFields() {
        return DbEngine::getCompiler()->inspectTable($this['table']);
    }
    
    /**
     * Create a new instance of the model, optionally hydrating it with the
     * given hash table. The constructor is not called, which leaves the 
     * default constructor free to assume new object status.
     */
    function newInstance($props=false) {
        static $sers = array();
        
        if (!isset($sers[$this->model])) {
            $sers[$this->model] = sprintf(
                'O:%d:"%s":0:{}',
                strlen($this->model), $this->model
            );
        }
        // TODO: Compare timing between unserialize() and
        //       ReflectionClass::newInstanceWithoutConstructor
        $instance = unserialize($sers[$this->model]);
        // Hydrate if props were included
        if (is_array($props)) {
            $instance->__ht__ = $props;
        }
        return $instance;
    }
}