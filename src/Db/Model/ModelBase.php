<?php

namespace Phlite\Db\Model;

use Phlite\Db\Exception;
use Phlite\Db\Manager;
use Phlite\Signal;
use Phlite\Util;

class ModelBase
implements \JsonSerializable {
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
        elseif (($joins = static::getMeta('joins')) && isset($joins[$field])) {
            $j = $joins[$field];
            // Support instrumented lists and such
            if (isset($j['list']) && $j['list']) {
                $class = $j['fkey'][0];
                $fkey = array();
                // Localize the foreign key constraint
                foreach ($j['constraint'] as $local=>$foreign) {
                    list($_klas,$F) = $foreign;
                    $fkey[$F ?: $_klas] = ($local[0] == "'")
                        ? trim($local, "'") : $this->ht[$local];
                }
                $v = $this->ht[$field] = new $j['broker'](
                    // Send Model, [Foriegn-Field => Local-Id]
                    array($class, $fkey)
                );
                return $v;
            }
            // Support relationships
            elseif (isset($j['fkey'])) {
                $criteria = array();
                foreach ($j['constraint'] as $local => $foreign) {
                    list($klas,$F) = $foreign;
                    if (class_exists($klas))
                        $class = $klas;
                    if ($local[0] == "'") {
                        $criteria[$F] = trim($local,"'");
                    }
                    elseif ($F[0] == "'") {
                        // Does not affect the local model
                        continue;
                    }
                    else {
                        if (!isset($this->__ht__[$local]))
                            // NULL foreign key
                            return null;
                        $criteria[$F] = $this->__ht__[$local];
                    }
                }
                try {
                    $v = $this->__ht__[$field] = $class::lookup($criteria);
                }
                catch (Exception\DoesNotExist $e) {
                    $v = null;
                }
                return $v;
            }
        }
        elseif (isset($this->__deferred__[$field])) {
            // Fetch deferred field
            $row = static::objects()->filter($this->getPk())
                // FIXME: Seems like all the deferred fields should be fetched
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

        // For new objects, assume the field is NULLable
        if ($this->__new__)
            return null;

        // Check to see if the column referenced is actually valid
        if (in_array($field, static::getMeta('fields')))
            return null;

        throw new Exception\OrmError(sprintf('%s: %s: Field not defined',
            get_class($this), $field));
    }
    function __get($field) {
        return $this->get($field, null);
    }

    function getByPath($path) {
        if (is_string($path))
            $path = explode('__', $path);
        $root = $this;
        foreach ($path as $P)
            if (!($root = $root->get($P)))
                break;
        return $root;
    }

    function __isset($field) {
        return array_key_exists($field, $this->__ht__)
            || isset(static::$meta['joins'][$field]);
    }
    function __unset($field) {
        if ($this->__isset($field))
            unset($this->__ht__[$field]);
        else
            unset($this->{$field});
        unset($this->__dirty__[$field]);
    }

    function set($field, $value) {
        // Update of foreign-key by assignment to model instance
        $related = false;
        $joins = static::getMeta('joins');
        if (isset($joins[$field])) {
            $j = $joins[$field];
            if ($j['list'] && ($value instanceof InstrumentedList)) {
                // Magic list property
                $this->__ht__[$field] = $value;
                return;
            }
            if ($value === null) {
                $this->__ht__[$field] = $value;
                if (in_array($j['local'], static::$meta['pk'])) {
                    // Reverse relationship — don't null out local PK
                    return;
                }
                // Pass. Set local field to NULL in logic below
            }
            elseif ($value instanceof $j['fkey'][0]) {
                // Capture the object under the object's field name
                $this->__ht__[$field] = $value;
                if ($value->__new__)
                    // save() will be performed when saving this object
                    $value = null;
                else
                    $value = $value->get($j['fkey'][1]);
                // Fall through to the standard logic below
            }
            else
                throw new \InvalidArgumentException(
                    sprintf(__('Expecting NULL or instance of %s. Got a %s instead'),
                    $j['fkey'][0], is_object($value) ? get_class($value) : gettype($value)));

            // Capture the foreign key id value
            $field = $j['local'];
        }
        // elseif $field is in a relationship, adjust the relationship
        elseif (isset(static::$meta['foreign_keys'][$field])) {
            // meta->foreign_keys->{$field} points to the property of the
            // foreign object. For instance 'object_id' points to 'object'
            $related = static::$meta['foreign_keys'][$field];
        }
        $old = isset($this->__ht__[$field]) ? $this->__ht__[$field] : null;
        if ($old != $value) {
            // isset should not be used here, because `null` should not be
            // replaced in the dirty array
            if (!array_key_exists($field, $this->__dirty__))
                $this->__dirty__[$field] = $old;
            if ($related)
                // $related points to a foreign object propery. If setting a
                // new object_id value, the relationship to object should be
                // cleared and rebuilt
                unset($this->__ht__[$related]);
        }
        $this->__ht__[$field] = $value;
    }
    function __set($field, $value) {
        return $this->set($field, $value);
    }

    function setAll($props) {
        foreach ($props as $field=>$value)
            $this->set($field, $value);
    }

    function __clone() {
        $this->__new__ = true;
        $this->__deleted__ = false;
        foreach (static::$meta['pk'] as $f)
            $this->__unset($f);
        $this->__dirty__ = array_fill(array_keys($this->__ht__), null);
    }

    function __onload() {}
    function __onsave() {}
    static function __oninspect() {}

    static function _inspect() {
        static::$meta = new ModelMeta(get_called_class());

        // Let the model participate
        static::__oninspect();
    }

    static function getMeta($key=false) {
        if (!static::$meta instanceof ModelMeta)
            static::_inspect();
        $M = static::$meta;
        return ($key) ? $M->offsetGet($key) : $M;
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
     * (Object<ModelBase>|null) a single instance of the sought model or
     * null if no such instance exists.
     *
     * Throws:
     * Db\Exception\NotUnique if the criteria does not hit a single object
     */
    static function lookup($criteria) {
        // Model::lookup(1), where >1< is the pk value
        if (!is_array($criteria)) {
            $criteria = array();
            $pk = static::getMeta('pk');
            foreach (func_get_args() as $i=>$f)
                $criteria[$pk[$i]] = $f;

            // Only consult cache for PK lookup, which is assumed if the
            // values are passed as args rather than an array
            if ($cached = ModelInstanceManager::checkCache(get_called_class(),
                    $criteria))
                return $cached;
        }

        return static::objects()->filter($criteria)->one();
    }

    function delete() {
        $ex = Manager::delete($this);
        try {
            $ex->execute();
            if ($ex->affected_rows() != 1)
                return false;

            $this->__deleted__ = true;
            Signal::send('model.deleted', $this);
        }
        catch (Exception\DbError $e) {
            return false;
        }
        return true;
    }

    function save($refetch=false) {
        if ($this->__deleted__)
            throw new OrmException('Trying to update a deleted object');

        $pk = static::getMeta('pk');
        $wasnew = $this->__new__;

        // First, if any foreign properties of this object are connected to
        // another *new* object, then save those objects first and set the
        // local foreign key field values
        foreach (static::getMeta('joins') as $prop => $j) {
            if (isset($this->ht[$prop])
                && ($foreign = $this->ht[$prop])
                && $foreign instanceof VerySimpleModel
                && !in_array($j['local'], $pk)
                && null === $this->get($j['local'])
            ) {
                if ($foreign->__new__ && !$foreign->save())
                    return false;
                $this->set($j['local'], $foreign->get($j['fkey'][1]));
            }
        }

        // If there's nothing in the model to be saved, then we're done
        if (count($this->__dirty__) === 0)
            return true;

        $ex = Manager::save($this);
        try {
            $ex->execute();
            if ($ex->affected_rows() != 1) {
                // This doesn't really signify an error. It just means that
                // the database believes that the row did not change. For
                // inserts though, it's a deal breaker
                if ($this->__new__) {
                    return false;
                }
                else {
                    // No need to reload the record if requested — the
                    // database didn't update anything
                    $refetch = false;
                }
            }
        }
        catch (Exception\OrmError $e) {
            return false;
        }

        if ($wasnew) {
            // XXX: Ensure AUTO_INCREMENT is set for the field
            if (count($pk) == 1 && !$refetch) {
                $key = $pk[0];
                $id = $ex->insert_id();
                if (!isset($this->{$key}) && $id)
                    $this->__ht__[$key] = $id;
            }
            $this->__new__ = false;
            Signal::send('model.created', $this);
        }
        else {
            $data = array('dirty' => $this->__dirty__);
            Signal::send('model.updated', $this, $data);
        }
        # Refetch row from database
        if ($refetch) {
            // Preserve non database information such as list relationships
            // across the refetch
            $this->__ht__ = static::objects()->filter($this->getPk())->values()->one()
                + $this->__ht__;
            // TODO: Cache $this object
        }
        if ($wasnew) {
            // Attempt to update foreign, unsaved objects with the PK of
            // this newly created object
            foreach (static::getMeta('joins') as $prop => $j) {
                if (isset($this->ht[$prop])
                    && ($foreign = $this->ht[$prop])
                    && in_array($j['local'], $pk)
                ) {
                    if ($foreign instanceof VerySimpleModel
                        && null === $foreign->get($j['fkey'][1])
                    ) {
                        $foreign->set($j['fkey'][1], $this->get($j['local']));
                    }
                    elseif ($foreign instanceof InstrumentedList) {
                        foreach ($foreign as $item) {
                            if (null === $item->get($j['fkey'][1]))
                                $item->set($j['fkey'][1], $this->get($j['local']));
                        }
                    }
                }
            }
            $this->__onsave();
        }
        $this->__dirty__ = array();
        return true;
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
        foreach ($this::getMeta('pk') as $f)
            $pk[$f] = $this->__ht__[$f];
        return $pk;
    }

    function __toString() {
        $a = new Util\ArrayObject($this->getPk());
        return sprintf('<%s %s>', get_class($this), $a->join('=',', '));
    }

    // ---- JsonSerializable interface ------------------------
    function jsonSerialize() {
        $fields = $this->__ht__;
        foreach (static::$meta['joins'] as $k=>$j) {
            // For join relationships, drop local ID from the fields
            // list. Instead, list the ID as the member of the join unless
            // it is an InstrumentedList or ModelBase instance, in which case
            // use it as is
            $L = @$fields[$k];
            if ($L instanceof InstrumentedList)
                // Don't drop the local ID field from the list
                continue;
             if (!$L instanceof ModelBase)
                 // Relationship to a deferred model. Use the local ID instead
                 $fields[$k] = $fields[$k['local']];
            unset($fields[$j['local']]);
        }
        return $fields;
    }
}
