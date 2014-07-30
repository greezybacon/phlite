<?php

namespace Phlite\Db;

class Model {
    static $meta = array(
        'table' => false,
        'ordering' => false,
        'pk' => false
    );

    var $__ht;
    var $__dirty;
    var $__new__ = false;

    function __construct($row) {
        $this->__ht = $row;
        $this->__setupForeignLists();
        $this->__dirty = array();
    }

    function get($field) {
        return $this->__ht[$field];
    }
    function __get($field) {
        if (array_key_exists($field, $this->__ht))
            return $this->__ht[$field];
        elseif (isset(static::$meta['joins'][$field])) {
            // TODO: Support instrumented lists and such
            $j = static::$meta['joins'][$field];
            $class = $j['fkey'][0];
            $v = $this->__ht[$field] = $class::lookup($this->__ht[$j['local']]);
            return $v;
        }
    }

    function set($field, $value) {
        // Update of foreign-key by assignment to model instance
        if (isset(static::$meta['joins'][$field])) {
            $j = static::$meta['joins'][$field];
            // XXX: Ensure $value instanceof $j['fkey'][0]
            if ($value->__new__)
                $value->save();
            // Capture the object under the object's field name
            $this->__ht[$field] = $value;
            // Capture the foreign key id value
            $field = $j['local'];
            $value = $value->{$j['fkey'][1]};
            // Fall through to the standard logic below
        }
        // XXX: Fully support or die if updating pk
        // XXX: The contents of $this->__dirty should be the value after the
        // previous fetch or save. For instance, if the value is changed more
        // than once, the original value should be preserved in the dirty list
        // on the second edit.
        $old = isset($this->__ht[$field]) ? $this->__ht[$field] : null;
        if ($old != $value) {
            $this->__dirty[$field] = $old;
            $this->__ht[$field] = $value;
        }
    }
    function __set($field, $value) {
        return $this->set($field, $value);
    }

    function setAll($props) {
        foreach ($props as $field=>$value)
            $this->set($field, $value);
    }

    function __setupForeignLists() {
        // Construct related lists
        if (isset(static::$meta['joins'])) {
            foreach (static::$meta['joins'] as $name => $j) {
                if (isset($j['list']) && $j['list']) {
                    $fkey = $j['fkey'];
                    $this->{$name} = new InstrumentedList(
                        // Send Model, Foriegn-Field, Local-Id
                        array($fkey[0], $fkey[1], $this->{$j['local']})
                    );
                }
            }
        }
    }

    static function _inspect() {
        if (!static::$meta['table'])
            throw new OrmConfigurationError(
                'Model does not define meta.table', get_called_class());

        // Break down foreign-key metadata
        foreach (static::$meta['joins'] as $field => &$j) {
            if (isset($j['reverse'])) {
                list($model, $key) = explode('.', $j['reverse']);
                $info = $model::$meta['joins'][$key];
                $constraint = array();
                foreach ($info['constraint'] as $foreign => $local) {
                    list(,$field) = explode('.', $local);
                    $constraint[$field] = "$model.$foreign";
                }
                $j['constraint'] = $constraint;
                $j['list'] = true;
            }
            // XXX: Make this better (ie. composite keys)
            $keys = array_keys($j['constraint']);
            $foreign = $j['constraint'][$keys[0]];
            $j['fkey'] = explode('.', $foreign);
            $j['local'] = $keys[0];
        }
    }

    static function objects() {
        return new QuerySet(get_called_class());
    }

    static function lookup($criteria) {
        if (!is_array($criteria))
            // Model::lookup(1), where >1< is the pk value
            $criteria = array(static::$meta['pk'][0] => $criteria);
        $list = static::objects()->filter($criteria)->limit(1);
        // TODO: Throw error if more than one result from database
        return $list[0];
    }

    function delete($pk=false) {
        $ex = MySqlEngine::delete($this);

        if (!$ex)
            // XXX: Do something useful
            return null;
        elseif (!$ex->affected_rows() != 1)
            throw new Exception(db_error());

        Signal::send('model.deleted', $this);
    }

    function save($refetch=false) {
        $ex = MySqlEngine::save($this);
        $ex = System::getDb($this)->save($this);
        if (!$pk) $pk = static::$meta['pk'];
        if (!is_array($pk)) $pk=array($pk);

        if ($this->__new__) {
            if (count($pk) == 1)
                $this->__ht[$pk[0]] = $ex->insert_id();

            $this->__new__ = false;
            // Setup lists again
            $this->__setupForeignLists();
            Signal::send('model.created', $this);
        }
        else {
            $data = array('dirty' => $this->__dirty);
            Signal::send('model.updated', $this, $data);
        }

        if (!$ex)
            // XXX: Do something useful
            return null;
        elseif (!$ex->affected_rows() != 1)
            throw new Exception(db_error());

        # Refetch row from database
        # XXX: Too much voodoo
        if ($refetch) {
            $criteria = array();
            foreach ($pk as $p)
                $criteria[$p] = $this->get($p);

            $self = static::lookup($criteria);
            $this->__ht = $self->__ht;
        }
        $this->__dirty = array();
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
}
