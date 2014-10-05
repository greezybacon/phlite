<?php

namespace Phlite\Db;

class ModelInstanceManager extends ResultSet {
    var $model;
    var $map;

    static $objectCache = array();

    function __construct($queryset=false) {
        parent::__construct($queryset);
        if ($queryset) {
            $this->map = $this->resource->getMap();
        }
    }

    function cache($model) {
        $model::_inspect();
        $key = sprintf('%s.%s',
            $model::$meta->model, implode('.', $model->pk));
        self::$objectCache[$key] = $model;
    }

    /**
     * uncache
     *
     * Drop the cached reference to the model. If the model is deleted
     * database-side. Lookups for the same model should not be short
     * circuited to retrieve the cached reference.
     */
    static function uncache($model) {
        $key = sprintf('%s.%s',
            $model::$meta->model, implode('.', $model->pk));
        unset(self::$objectCache[$key]);
    }

    static function checkCache($modelClass, $fields) {
        $key = $modelClass::$meta->model;
        foreach ($modelClass::$meta['pk'] as $f)
            $key .= '.'.$fields[$f];
        return @self::$objectCache[$key];
    }

    function getOrBuild($modelClass, $fields) {
        // Check the cache for the model instance first
        if ($m = self::checkCache($modelClass, $fields)) {
            // TODO: If the model has deferred fields which are in $fields,
            // those can be resolved here
            return $m;
        }
        // Construct and cache the object
        $this->cache($m = new $modelClass($fields));
        $m->__deferred__ = $this->queryset->defer;
        $m->__onload();
        return $m;
    }

    /**
     * buildModel
     *
     * This method builds the model including related models from the record
     * received. For related recordsets, a $map should be setup inside this
     * object prior to using this method. The $map is assumed to have this
     * configuration:
     *
     * array(array(<fieldNames>, <modelClass>, <relativePath>))
     *
     * Where $modelClass is the name of the foreign (with respect to the
     * root model ($this->model), $fieldNames is the number and names of
     * fields in the row for this model, $relativePath is the path that
     * describes the relationship between the root model and this model,
     * 'user__account' for instance.
     */
    function buildModel($row) {
        // TODO: Traverse to foreign keys
        if ($this->map) {
            if ($this->model != $this->map[0][1])
                throw new Exception\OrmError('Internal select_related error');

            $offset = 0;
            foreach ($this->map as $info) {
                @list($fields, $model_class, $path) = $info;
                $values = array_slice($row, $offset, count($fields));
                $record = array_combine($fields, $values);
                if (!$path) {
                    // Build the root model
                    $model = $this->getOrBuild($this->model, $record);
                }
                else {
                    $i = 0;
                    // Traverse the declared path and link the related model
                    $tail = array_pop($path);
                    $m = $model;
                    foreach ($path as $field) {
                        $m = $m->get($field);
                    }
                    $m->set($tail, $this->getOrBuild($model_class, $record));
                }
                $offset += count($fields);
            }
        }
        else {
            $model = $this->getOrBuild($this->model, $row);
        }
        return $model;
    }

    function fillTo($index) {
        $func = ($this->map) ? 'getRow' : 'getArray';
        while ($this->resource && $index >= count($this->cache)) {
            if ($row = $this->resource->{$func}()) {
                $this->cache[] = $this->buildModel($row);
            } else {
                $this->resource->close();
                $this->resource = null;
                break;
            }
        }
    }
}