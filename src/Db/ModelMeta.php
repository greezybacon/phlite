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

    static $base = array(
        'pk' => false,
        'table' => false,
        'defer' => array(),
        'select_related' => array(),
    );
    
    var $model;

    function __construct($model) {
        $this->model = $model;
        $meta = $model::$meta + self::$base;

        // TODO: Merge ModelMeta from parent model (if inherited)

        if (!$meta['table'])
            throw new Exception\ModelConfigurationError(
                __('Model does not define meta.table'), $model);
        elseif (!$meta['pk'])
            throw new Exception\ModelConfigurationError(
                __('Model does not define meta.pk'), $model);

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
            if (isset($j['reverse'])) {
                list($fmodel, $key) = explode('.', $j['reverse']);
                $info = $fmodel::$meta['joins'][$key];
                $constraint = array();
                if (!is_array($info['constraint']))
                    throw new Exception\ModelConfigurationError(sprintf(__(
                        // `reverse` here is the reverse of an ORM relationship
                        '%s: Reverse does not specify any constraints'),
                        $j['reverse']));
                foreach ($info['constraint'] as $foreign => $local) {
                    list(,$field) = explode('.', $local);
                    $constraint[$field] = "$fmodel.$foreign";
                }
                $j['constraint'] = $constraint;
                if (!isset($j['list']))
                    $j['list'] = true;
                $j['null'] = $info['null'] ?: false;
            }
            // XXX: Make this better (ie. composite keys)
            $keys = array_keys($j['constraint']);
            $foreign = $j['constraint'][$keys[0]];
            $j['fkey'] = explode('.', $foreign);
            $j['local'] = $keys[0];
        }
        unset($j);
        $this->base = $meta;
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
        case 'newInstance':
            $class_repr = sprintf(
                'O:%d:"%s":0:{}',
                strlen($this->model), $this->model
            );
            $this->base['newInstance'] = function() use ($class_repr) {
                return unserialize($class_repr);
            };
            break;
        default:
            throw new \Exception($what . ': No such meta-data');
        }
    }

    function inspectFields() {
        return DbEngine::getCompiler()->inspectTable($this['table']);
    }
}