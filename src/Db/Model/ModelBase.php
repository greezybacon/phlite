<?php

namespace Phlite\Db\Model;

use Phlite\Db\Exception;
use Phlite\Db\Manager;

class ModelBase {
    static $meta = array(
        'table' => false,
        'ordering' => false,
        'pk' => false
    );

    var $__ht__;
    var $__dirty__ = array();
    var $__new__ = false;
    var $__deleted__ = false;
    var $__deferred__ = array();

    function __construct(array $row) {
        $this->__ht__ = $row;
    }

    function get($field, $default=false) {
        if (array_key_exists($field, $this->__ht__))
            return $this->__ht__[$field];
        elseif (isset(static::$meta['joins'][$field])) {
            // Make sure joins were inspected
            if (!static::$meta instanceof ModelMeta)
                static::_inspect();
            $j = static::$meta['joins'][$field];
            // Support instrumented lists and such
            if (isset($this->__ht__[$j['local']])
                    && isset($j['list']) && $j['list']) {
                $fkey = $j['fkey'];
                $v = $this->__ht__[$field] = new InstrumentedList(
                    // Send Model, Foriegn-Field, Local-Id
                    array($fkey[0], $fkey[1], $this->get($j['local']))
                );
                return $v;
            }
            // Support relationships
            elseif (isset($j['fkey'])
                    && ($class = $j['fkey'][0])
                    && class_exists($class)) {
                return $this->__ht__[$field] = $class::lookup(
                    array($j['fkey'][1] => $this->__ht__[$j['local']]));
            }
        }
        elseif (isset($this->__deferred__[$field])) {
            // Fetch deferred field
            $row = static::objects()->filter($this->getPk())
                ->values_flat($field)
                ->one();
            if ($row)
                return $this->__ht__[$field] = $row[0];
        }
        elseif ($field == 'pk') {
            return $this->getPk();
        }

        if (isset($default))
            return $default;
        // TODO: Inspect fields from database before throwing this error
        throw new Exception\OrmError(sprintf(__('%s: %s: Field not defined'),
            get_class($this), $field));
    }
    function __get($field) {
        return $this->get($field, null);
    }

    function __isset($field) {
        return array_key_exists($field, $this->__ht__)
            || isset(static::$meta['joins'][$field]);
    }
    function __unset($field) {
        unset($this->__ht__[$field]);
    }

    function set($field, $value) {
        // Update of foreign-key by assignment to model instance
        if (isset(static::$meta['joins'][$field])) {
            static::_inspect();
            $j = static::$meta['joins'][$field];
            if ($j['list'] && ($value instanceof InstrumentedList)) {
                // Magic list property
                $this->__ht__[$field] = $value;
                return;
            }
            if ($value === null) {
                // Pass. Set local field to NULL in logic below
            }
            elseif ($value instanceof $j['fkey'][0]) {
                if ($value->__new__)
                    $value->save();
                // Capture the object under the object's field name
                $this->__ht__[$field] = $value;
                $value = $value->get($j['fkey'][1]);
                // Fall through to the standard logic below
            }
            else
                throw new \InvalidArgumentException(
                    sprintf(__('Expecting NULL or instance of %s. Got a %s instead'),
                    $j['fkey'][0], get_class($value)));

            // Capture the foreign key id value
            $field = $j['local'];
        }
        $old = isset($this->__ht__[$field]) ? $this->__ht__[$field] : null;
        if ($old != $value) {
            // isset should not be used here, because `null` should not be
            // replaced in the dirty array
            if (!array_key_exists($field, $this->__dirty__))
                $this->__dirty__[$field] = $old;
            $this->__ht__[$field] = $value;
        }
    }
    function __set($field, $value) {
        return $this->set($field, $value);
    }

    function setAll($props) {
        foreach ($props as $field=>$value)
            $this->set($field, $value);
    }

    function __onload() {}
    static function __oninspect() {}

    static function _inspect() {
        if (!static::$meta instanceof ModelMeta) {
            static::$meta = new ModelMeta(get_called_class());

            // Let the model participate
            static::__oninspect();
        }
    }

    /**
     * objects
     *
     * Retrieve a QuerySet for this model class which can be used to fetch
     * models from the connected database. Subclasses can override this
     * method to apply forced constraints on the QuerySet.
     */
    static function objects() {
        return new QuerySet(get_called_class());
    }

    /**
     * lookup
     * 
     * Retrieve a record by its primary key. This method may be short
     * circuited by model caching if the record has already been loaded by
     * the database. In such a case, the database will not be consulted for
     * the model's data.
     *
     * This method can be called with an array of keyword arguments matching
     * the PK of the object or the values of the primary key. Both of these
     * usages are correct:
     *
     * >>> User::lookup(1)
     * >>> User::lookup(array('id'=>1))
     *
     * For composite primary keys and the first usage, pass the values in
     * the order they are given in the Model's 'pk' declaration in its meta
     * data. For example:
     *
     * >>> UserPrivilege::lookup(1, 2)
     *
     * Parameters:
     * $criteria - (mixed) primary key for the sought model either as
     *      arguments or key/value array as the function's first argument
     *
     * Returns:
     * <ModelBase> instance if the lookup succeeded, and NULL otherwise.
     *
     * Throws:
     * Db\Exception\NotUnique if the criteria does not hit a single object
     */
    static function lookup($criteria) {
        // Model::lookup(1), where >1< is the pk value
        if (!is_array($criteria)) {
            $criteria = array();
            foreach (func_get_args() as $i=>$f)
                $criteria[static::$meta['pk'][$i]] = $f;
        }
        if ($cached = ModelInstanceManager::checkCache(get_called_class(),
                $criteria))
            return $cached;
        
        try {
            return static::objects()->filter($criteria)->one();
        }
        catch (Exception\DoesNotExist $e) {
            return null;
        }
    }

    function delete($pk=false) {
        $ex = Manager::delete($this);
        try {
            $ex->execute();
            if ($ex->affected_rows() != 1)
                return false;

            $this->__deleted__ = true;
            Signal::send('model.deleted', $this);
        }
        catch (DbError $e) {
            return false;
        }
        return true;
    }

    function save($refetch=false) {
        if (count($this->__dirty__) === 0)
            return true;
        elseif ($this->__deleted__)
            throw new Exception\OrmError('Trying to update a deleted object');

        $ex = Manager::save($this);
        try {
            $ex->execute();
            if ($ex->affected_rows() != 1)
                return false;
        }
        catch (Exception\OrmError $e) {
            return false;
        }

        $pk = static::$meta['pk'];

        if ($this->__new__) {
            if (count($pk) == 1)
                // XXX: Ensure AUTO_INCREMENT is set for the field
                $this->__ht__[$pk[0]] = $ex->insert_id();
            $this->__new__ = false;
            Signal::send('model.created', $this);
            $this->__onload();
        }
        else {
            $data = array('dirty' => $this->__dirty__);
            Signal::send('model.updated', $this, $data);
        }
        # Refetch row from database
        # XXX: Too much voodoo
        if ($refetch) {
            // Uncache so that the lookup will not be short-cirtuited to
            // return this object — i.e. actually fetch from database
            ModelInstanceManager::uncache($this);
            $self = static::lookup($this->get('pk'));
            $this->__ht__ = $self->__ht__;
        }
        $this->__dirty__ = array();
        return $this->get($pk[0]);
    }

    static function create($ht=false) {
        if (!$ht) $ht=array();
        $class = get_called_class();
        $i = new $class(array());
        $i->__new__ = true;
        foreach ($ht as $field=>$value)
            if (!is_array($value))
                $i->set($field, $value);
        return $i;
    }

    private function getPk() {
        $pk = array();
        foreach ($this::$meta['pk'] as $f)
            $pk[$f] = $this->__ht__[$f];
        return $pk;
    }
}
