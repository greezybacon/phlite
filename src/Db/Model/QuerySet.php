<?php

namespace Phlite\Db\Model;

use Phlite\Db\Exception;
use Phlite\Db\Manager;
use Phlite\Db\Util;

class QuerySet
implements \IteratorAggregate, \ArrayAccess, \Serializable, \Countable {
    var $model;

    var $constraints = array();
    var $path_constraints = array();
    var $ordering = array();
    var $limit = false;
    var $offset = 0;
    var $related = array();
    var $values = array();
    var $defer = array();
    var $aggregated = false;
    var $annotations = array();
    var $lock = false;
    var $extra = array();
    var $distinct = array();
    var $chain = array();
    var $options = array();

    const LOCK_EXCLUSIVE = 1;
    const LOCK_SHARED = 2;

    const ASC = 'ASC';
    const DESC = 'DESC';

    var $iterator = 'Phlite\Db\Model\ModelInstanceManager';

    var $query;
    var $_count;
    var $_iterator;

    function __construct($model) {
        $this->model = $model;
    }

    function filter() {
        foreach (func_get_args() as $Q) {
            $this->constraints[] = $Q instanceof Util\Q ? $Q : new Util\Q($Q);
        }
        return $this;
    }

    function exclude() {
        foreach (func_get_args() as $Q) {
            $this->constraints[] = $Q instanceof Util\Q ? $Q->negate() : Util\Q::not($Q);
        }
        return $this;
    }

    /**
     * Add a path constraint for the query. This is different from ::filter
     * in that the constraint is added to a join clause which is normally
     * built from the model meta data. The ::filter() method on the other
     * hand adds the constraint to the where clause. This is generally useful
     * for aggregate queries and left join queries where multiple rows might
     * match a filter in the where clause and would produce incorrect results.
     *
     * Example:
     * Find users with personal email hosted with gmail.
     * >>> $Q = User::objects();
     * >>> $Q->constrain(['user__emails' => new Q(['type' => 'personal']))
     * >>> $Q->filter(['user__emails__address__contains' => '@gmail.com'])
     */
    function constrain() {
        foreach (func_get_args() as $join=>$I) {
            foreach ($I as $path => $Q) {
                // TODO: Consider ensure / auto-adding $path to all paths
                // listed in $Q
                if (!is_array($Q) && !$Q instanceof Q) {
                    // ->constrain(array('field__path__op' => val));
                    $Q = array($path => $Q);
                    list(, $path) = SqlCompiler::splitCriteria($path);
                    $path = implode('__', $path);
                }
                $this->path_constraints[$path][] = $Q instanceof Q ? $Q : Q::all($Q);
            }
        }
        return $this;
    }

    function defer() {
        foreach (func_get_args() as $f)
            $this->defer[$f] = true;
        return $this;
    }

    function order_by($order, $direction=false) {
        if ($order === false)
            return $this->options(array('nosort' => true));

        $args = func_get_args();
        if (in_array($direction, array(self::ASC, self::DESC))) {
            $args = array($args[0]);
        }
        else
            $direction = false;

        $new = is_array($order) ?  $order : $args;
        if ($direction) {
            foreach ($new as $i=>$x) {
                $new[$i] = array($x, $direction);
            }
        }
        $this->ordering = array_merge($this->ordering, $new);
        return $this;
    }

    function options($options) {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    function getSortFields() {
        $ordering = $this->ordering;
        if (isset($this->extra['order_by']))
            $ordering = array_merge($ordering, $this->extra['order_by']);
        return $ordering;
    }

    function countSelectFields() {
        $count = count($this->values) + count($this->annotations);
        if (isset($this->extra['select']))
            foreach (@$this->extra['select'] as $S)
                $count += count($S);
        return $count;
    }

    function for_update() {
        return $this->lock(self::LOCK_EXCLUSIVE);
    }
    function lock($how=false) {
        $this->lock = $how ?: self::LOCK_EXCLUSIVE;
        return $this;
    }

    function limit($count) {
        $this->limit = $count;
        return $this;
    }

    function offset($at) {
        $this->offset = $at;
        return $this;
    }

    function isWindowed() {
        return $this->limit || $this->offset;
    }

    function select_related() {
        $this->related = array_merge($this->related, func_get_args());
        return $this;
    }

    function extra(array $extra) {
        foreach ($extra as $section=>$info) {
            $this->extra[$section] = array_merge($this->extra[$section] ?: array(), $info);
        }
        return $this;
    }

    function distinct() {
        foreach (func_get_args() as $D)
            $this->distinct[] = $D;
        return $this;
    }

    function models() {
        $this->iterator = $this->iterator;
        $this->values = $this->related = array();
        return $this;
    }

    /**
     * Instead of returning objects of the root model, return a hash array
     * where the keys are the field names passed in here, and the values
     * are the values from the database. This function can be called more
     * than once. Each time, the arguments are added to the use of values
     * retrieved from the database.
     */
    function values() {
        foreach (func_get_args() as $A)
            $this->values[$A] = $A;
        $this->iterator = __NAMESPACE__.'\HashArrayIterator';
        // This disables related models
        $this->related = false;
        return $this;
    }

    function values_flat() {
        $this->values = func_get_args();
        $this->iterator = __NAMESPACE__.'\FlatArrayIterator';
        // This disables related models
        $this->related = false;
        return $this;
    }

    function copy() {
        return clone $this;
    }

    function all() {
        return $this->getIterator();
    }

    function first() {
        $this->limit(1);
        return $this[0];
    }

    /**
     * one
     *
     * Finds and returns a single model instance based on the criteria in
     * this QuerySet instance.
     *
     * Throws:
     * DoesNotExist - if no such model exists with the given criteria
     * ObjectNotUnique - if more than one model matches the given criteria
     *
     * Returns:
     * (Object<Model>) a single instance of the sought model is guarenteed.
     * If no such model or multiple models exist, an exception is thrown.
     */
    function one() {
        $list = $this->all();
        if (count($list) == 0)
            throw new Exception\DoesNotExist();
        elseif (count($list) > 1)
            throw new Exception\NotUnique('One object was expected; however '
                .'multiple objects in the database matched the query. '
                .sprintf('In fact, there are %d matching objects.', count($list))
            );
        return $list[0];
    }

    /**
     * count
     *
     * Fetch a count of records represented by this QuerySet. If not already
     * fetching, a SELECT COUNT(*) query will be requested of the database
     * and cached locally. Multiple calls to this method will receive the
     * cached value. If already fetching from the recordset, the rest of the
     * records will be retrieved and the count of those records will be
     * returned.
     *
     * Returns:
     * <int> — number of records matched by this QuerySet
     */
    function count() {
        // Defer to the iterator if fetching already started
        if (isset($this->_iterator)) {
            return $this->_iterator->count();
        }
        // Returned cached count if available
        elseif (isset($this->_count)) {
            return $this->_count;
        }
        $connection = Manager::getConnection($this->model);
        $compiler = $this->getCompiler();
        $stmt = $compiler->compileCount($this);
        $exec = $connection->execute($stmt);
        $row = $exec->fetchRow();
        return $this->_count = $row[0];
    }

    function toSql($compiler, $model, $alias=false) {
        // FIXME: Force root model of the compiler to $model
        $exec = $this->getQuery(array('compiler' => get_class($compiler),
             'parent' => $compiler, 'subquery' => true));
        // Rewrite the parameter numbers so they fit the parameter numbers
        // of the current parameters of the $compiler
        $sql = preg_replace_callback("/:(\d+)/",
        function($m) use ($compiler, $exec) {
            $compiler->params[] = $exec->params[$m[1]-1];
            return ':'.count($compiler->params);
        }, $exec->sql);
        return "({$sql})".($alias ? " AS {$alias}" : '');
    }

    /**
     * exists
     *
     * Determines if there are any rows in this QuerySet. This can be
     * achieved either by evaluating a SELECT COUNT(*) query or by
     * attempting to fetch the first row from the recordset and return
     * boolean success.
     *
     * Parameters:
     * $fetch - (bool) TRUE if a compile and fetch should be attempted
     *      instead of a SELECT COUNT(*). This would be recommended if an
     *      accurate count is not required and the records would be fetched
     *      if this method returns TRUE.
     *
     * Returns:
     * (bool) TRUE if there would be at least one record in this QuerySet
     */
    function exists($fetch=false) {
        if ($fetch) {
            return (bool) $this[0];
        }
        return $this->count() > 0;
    }

    function annotate($annotations) {
        if (!is_array($annotations))
            $annotations = func_get_args();
        foreach ($annotations as $name=>$A) {
            if ($A instanceof Util\Aggregate) {
                if (is_int($name))
                    $name = $A->getFieldName();
                $A->setAlias($name);
            }
            $this->annotations[$name] = $A;
        }
        return $this;
    }

    function aggregate($annotations) {
        // Aggregate works like annotate, except that it sets up values
        // fetching which will disable model creation
        $this->annotate($annotations);
        $this->values();
        // Disable other fields from being fetched
        $this->aggregated = true;
        $this->related = false;
        return $this;
    }

    function union(QuerySet $other, $all=true) {
        // Values and values_list _must_ match for this to work
        if ($this->countSelectFields() != $other->countSelectFields())
            throw new Exception\OrmError('Union queries must have matching values counts');

        // TODO: Clear OFFSET and LIMIT in the $other query

        $this->chain[] = array($other, $all);
        return $this;
    }

    protected function getCompiler() {
        $connection = Manager::getConnection($this->model);
        return $connection->getCompiler();
    }

    function delete() {
        $connection = Manager::getConnection($this->model);
        $compiler = $connection->getCompiler();
        // XXX: Mark all in-memory cached objects as deleted
        $stmt = $compiler->compileBulkDelete($this);
        $exec = $connection->execute($stmt);
        return $exec->affected_rows();
    }

    function update(array $what) {
        $connection = Manager::getConnection($this->model);
        $compiler = $connection->getCompiler();
        $stmt = $compiler->compileBulkUpdate($this, $what);
        $exec = $connection->execute($stmt);
        return $exec->affected_rows();
    }

    function __clone() {
        unset($this->_iterator);
        unset($this->_count);
        unset($this->query);
    }

    // IteratorAggregate interface
    function getIterator() {
        $class = $this->iterator;
        if (!isset($this->_iterator))
            $this->_iterator = new $class($this);
        return $this->_iterator;
    }

    // ArrayAccess interface
    function offsetExists($offset) {
        return $this->getIterator()->offsetExists($offset);
    }
    function offsetGet($offset) {
        return $this->getIterator()->offsetGet($offset);
    }
    function offsetUnset($a) {
        throw new \Exception(__('QuerySet is read-only'));
    }
    function offsetSet($a, $b) {
        throw new \Exception(__('QuerySet is read-only'));
    }

    function __toString() {
        return (string) $this->getQuery();
    }

    function getQuery($options=array()) {
        if (isset($this->query))
            return $this->query;

        // Load defaults from model
        $model = $this->model;
        $model::_inspect();

        $query = clone $this;
        $options += $this->options;
        // Be careful not to make local modifications based on model meta
        // compilation preferences
        if ($options['nosort'])
            $query->ordering = array();
        elseif (!$query->ordering && $model::getMeta('ordering'))
            $query->ordering = $model::$meta['ordering'];
        if (false !== $query->related && !$query->values && $model::getMeta('select_related'))
            $query->related = $model::getMeta('select_related');
        if (!$query->defer && $model::getMeta('defer'))
            $query->defer = $model::getMeta('defer');

        $class = $options['compiler'] ?: $this->compiler;
        $connection = Manager::getConnection($model);
        $compiler = $connection->getCompiler();
        return $this->query = $compiler->compileSelect($query);
    }

    /**
     * Fetch a model class which can be used to render the QuerySet as a
     * subquery to be used as a JOIN.
     */
    function asView() {
        $unique = spl_object_hash($this);
        $classname = "QueryView{$unique}";
        $class = <<<EOF
class {$classname} extends VerySimpleModel {
    static \$meta = array(
        'view' => true,
    );
    static \$queryset;

    static function getQuery(\$compiler) {
        return ' ('.static::\$queryset->getQuery().') ';
    }

    static function getSqlAddParams(\$compiler) {
        return static::\$queryset->toSql(\$compiler, self::\$queryset->model);
    }
}
EOF;
        eval($class); // Ugh
        $classname::$queryset = $this;
        return $classname;
    }

    function serialize() {
        $info = get_object_vars($this);
        unset($info['query']);
        unset($info['limit']);
        unset($info['offset']);
        unset($info['_iterator']);
        unset($info['_count']);
        return serialize($info);
    }

    function unserialize($data) {
        $data = unserialize($data);
        foreach ($data as $name => $val) {
            $this->{$name} = $val;
        }
    }
}
