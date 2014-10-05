<?php

namespace Phlite\Db;

abstract class SqlCompiler {

    // Consts for ::input()
    const SLOT_JOINS = 1;
    const SLOT_WHERE = 2;

    var $options = array();
    var $joins = array();
    var $aliases = array();
    var $alias_num = 1;

    protected $params = array();
    protected $conn;

    static $operators = array(
        'exact' => '%$1s = %$2s'
    );

    function __construct(Connection $conn, $options=false) {
        if ($options)
            $this->options = array_merge($this->options, $options);
        $this->conn = $conn;
    }

    /**
     * Handles breaking down a field or model search descriptor into the
     * model search path, field, and operator parts. When used in a queryset
     * filter, an expression such as
     *
     * user__email__hostname__contains => 'foobar'
     *
     * would be broken down to search from the root model (passed in,
     * perhaps a ticket) to the user and email models by inspecting the
     * model metadata 'joins' property. The 'constraint' value found there
     * will be used to build the JOIN sql clauses.
     *
     * The 'hostname' will be the field on 'email' model that should be
     * compared in the WHERE clause. The comparison should be made using a
     * 'contains' function, which in MySQL, might be implemented using
     * something like "<lhs> LIKE '%foobar%'"
     *
     * This function will rely heavily on the pushJoin() function which will
     * handle keeping track of joins made previously in the query and
     * therefore prevent multiple joins to the same table for the same
     * reason. (Self joins are still supported).
     *
     * Comparison functions supported by this function are defined for each
     * respective SqlCompiler subclass; however at least these functions
     * should be defined:
     *
     *      function    a__function => b
     *      ----------+------------------------------------------------
     *      exact     | a is exactly equal to b
     *      gt        | a is greater than b
     *      lte       | b is greater than a
     *      lt        | a is less than b
     *      gte       | b is less than a
     *      ----------+------------------------------------------------
     *      contains  | (string) b is contained within a
     *      statswith | (string) first len(b) chars of a are exactly b
     *      endswith  | (string) last len(b) chars of a are exactly b
     *      like      | (string) a matches pattern b
     *      ----------+------------------------------------------------
     *      in        | a is in the list or the nested queryset b
     *      ----------+------------------------------------------------
     *      isnull    | a is null (if b) else a is not null
     *
     * If no comparison function is declared in the field descriptor,
     * 'exact' is assumed.
     *
     * Parameters:
     * $field - (string) name of the field to join
     * $model - (VerySimpleModel) root model for references in the $field
     *      parameter
     * $options - (array) extra options for the compiler
     *      'table' => return the table alias rather than the field-name
     *      'model' => return the target model class rather than the operator
     *      'constraint' => extra constraint for join clause
     *
     * Returns:
     * (mixed) Usually array<field-name, operator> where field-name is the
     * name of the field in the destination model, and operator is the
     * requestion comparison method.
     */
    function getField($field, $model, $options=array()) {
        // Break apart the field descriptor by __ (double-underbars). The
        // first part is assumed to be the root field in the given model.
        // The parts after each of the __ pieces are links to other tables.
        // The last item (after the last __) is allowed to be an operator
        // specifiction.
        $parts = explode('__', $field);
        $operator = static::$operators['exact'];
        if (!isset($options['table'])) {
            $field = array_pop($parts);
            if (array_key_exists($field, static::$operators)) {
                $operator = static::$operators[$field];
                $field = array_pop($parts);
            }
        }

        $path = array();

        // Determine the alias for the root model table
        $alias = (isset($this->joins['']))
            ? $this->joins['']['alias']
            : $this->quote($model::$meta['table']);

        // Call pushJoin for each segment in the join path. A new JOIN
        // fragment will need to be emitted and/or cached
        $push = function($p, $path, $extra=false) use (&$model) {
            $model::_inspect();
            if (!($info = $model::$meta['joins'][$p])) {
                throw new Exception\OrmError(sprintf(
                   'Model `%s` does not have a relation called `%s`',
                    $model, $p));
            }
            $crumb = implode('__', $path);
            $path[] = $p;
            $tip = implode('__', $path);
            $alias = $this->pushJoin($crumb, $tip, $model, $info, $extra);
            // Roll to foreign model
            foreach ($info['constraint'] as $local => $foreign) {
                list($model, $f) = explode('.', $foreign);
                if (class_exists($model))
                    break;
            }
            return array($alias, $f);
        };

        foreach ($parts as $i=>$p) {
            list($alias) = $push($p, $path, @$options['constraint']);
            $path[] = $p;
        }

        // If comparing a relationship, join the foreign table
        // This is a comparison with a relationship — use the foreign key
        if (isset($model::$meta['joins'][$field])) {
            list($alias, $field) = $push($field, $path, @$options['constraint']);
        }

        if (isset($options['table']) && $options['table'])
            $field = $alias;
        elseif (isset($this->annotations[$field]))
            $field = $this->annotations[$field];
        elseif ($alias)
            $field = $alias.'.'.$this->quote($field);
        else
            $field = $this->quote($field);
        if (isset($options['model']) && $options['model'])
            $operator = $model;
        return array($field, $operator);
    }

    /**
     * Uses the compiler-specific `compileJoin` function to compile the join
     * statement fragment, and caches the result in the local $joins list. A
     * new alias is acquired using the `nextAlias` function which will be
     * associated with the join. If the same path is requested again, the
     * algorithm is short-circuited and the originally-assigned table alias
     * is returned immediately.
     */
    function pushJoin($tip, $path, $model, $info, $constraint=false) {
        // TODO: Build the join statement fragment and return the table
        // alias. The table alias will be useful where the join is used in
        // the WHERE and ORDER BY clauses

        // If the join already exists for the statement-being-compiled, just
        // return the alias being used.
        if (!$constraint && isset($this->joins[$path]))
            return $this->joins[$path]['alias'];

        // TODO: Support only using aliases if necessary. Use actual table
        // names for everything except oddities like self-joins

        $alias = $this->nextAlias();
        // Keep an association between the table alias and the model. This
        // will make model construction much easier when we have the data
        // and the table alias from the database.
        $this->aliases[$alias] = $model;

        // TODO: Stash joins and join constraints into local ->joins array.
        // This will be useful metadata in the executor to construct the
        // final models for fetching
        // TODO: Always use a table alias. This will further help with
        // coordination between the data returned from the database (where
        // table alias is available) and the corresponding data.
        $T = array('alias' => $alias);
        $this->joins[$path] = &$T;
        $T['sql'] = $this->compileJoin($tip, $model, $alias, $info, $constraint);
        return $alias;
    }
    
    abstract function compileJoin($tip, $model, $alias, $info, $extra=false);

    /**
     * compileQ
     *
     * Build a constraint represented in an arbitrarily nested Q instance.
     * The placement of the compiled constraint is also considered and
     * represented in the resulting CompiledExpression instance.
     *
     * Parameters:
     * $Q - (Q) constraint represented in a Q instance
     * $model - (VerySimpleModel) root model for all the field references in
     *      the Q instance
     * $slot - (int) slot for inputs to be placed. Useful to differenciate
     *      inputs placed in the joins and where clauses for SQL backends
     *      which do not support named parameters.
     *
     * Returns:
     * (CompiledExpression) object containing the compiled expression (with
     * AND, OR, and NOT operators added). Furthermore, the $type attribute
     * of the CompiledExpression will allow the compiler to place the
     * constraint properly in the WHERE or HAVING clause appropriately.
     */
    function compileQ(Util\Q $Q, $model, $slot=false) {
        $filter = array();
        $type = CompiledExpression::TYPE_WHERE;
        foreach ($Q->constraints as $field=>$value) {
            // Handle nested constraints
            if ($value instanceof Util\Q) {
                $filter[] = $T = $this->compileQ($value, $model, $slot);
                // Bubble up HAVING constraints
                if ($T instanceof CompiledExpression
                        && $T->type == CompiledExpression::TYPE_HAVING)
                    $type = $T->type;
            }
            // Handle relationship comparisons with model objects
            elseif ($value instanceof ModelBase) {
                $criteria = array();
                foreach ($value->pk as $f=>$v) {
                    $f = $field . '__' . $f;
                    $criteria[$f] = $v;
                }
                $filter[] = $this->compileQ(new Q($criteria), $model, $slot);
            }
            // Handle simple field = <value> constraints
            else {
                list($field, $op) = $this->getField($field, $model);
                if ($field instanceof Util\Aggregate) {
                    // This constraint has to go in the HAVING clause
                    $field = $field->toSql($this, $model);
                    $type = CompiledExpression::TYPE_HAVING;
                }
                if ($value === null)
                    $filter[] = sprintf('%s IS NULL', $field);
                // Allow operators to be callable rather than sprintf
                // strings
                elseif (is_callable($op))
                    $filter[] = call_user_func($op, $field, $value, $model);
                else
                    $filter[] = sprintf($op, $field, $this->input($value, $slot));
            }
        }
        $glue = $Q->isOred() ? ' OR ' : ' AND ';
        $clause = implode($glue, $filter);
        if (count($filter) > 1)
            $clause = '(' . $clause . ')';
        if ($Q->isNegated())
            $clause = 'NOT '.$clause;
        return new CompiledExpression($clause, $type);
    }

    function compileConstraints($where, $model) {
        $constraints = array();
        foreach ($where as $Q) {
            $constraints[] = $this->compileQ($Q, $model);
        }
        return $constraints;
    }
    
    /**
     * input
     *
     * Generate a parameterized input for a database query. Input value is
     * received by reference to avoid copying.
     *
     * Parameters:
     * $what - (mixed) value to be sent to the database. No escaping is
     *      necessary. Pass a raw value here.
     * $slot - (int) clause location of the input in compiled SQL statement.
     *      Currently, SLOT_JOINS and SLOT_WHERE is supported. SLOT_JOINS
     *      inputs are inserted ahead of the SLOT_WHERE inputs as the joins
     *      come logically before the where claused in the finalized
     *      statement.
     *
     * Returns:
     * (string) token to be placed into the compiled SQL statement. For
     * MySQL, this is always the string '?'. Depends on the actual backend
     * implementation.
     */
    function input($what, $slot=false) {
        if ($what instanceof QuerySet) {
            $q = $what->getQuery(array('nosort'=>true));
            $this->params = array_merge($q->params);
            return (string)$q;
        }
        elseif ($what instanceof SqlFunction) {
            return $what->toSql($this);
        }
        else {
            $this->addParam($what, $slot);
        }
    }
    
    /**
     * Add a parameter to the internal parameters list ($this->params).
     * This is the part of ::input() that is specific to the database backend
     * implementation.
     *
     * Parameters:
     * @see ::input() documentation.
     *
     * Returns:
     * (String) string to be embedded in the statement where the parameter
     * should be used server-side.
     */
    abstract function addParam($what, $param=false);

    function getParams() {
        return $this->params;
    }

    function getJoins() {
        $sql = '';
        foreach ($this->joins as $j)
            $sql .= $j['sql'];
        return $sql;
    }
    
    /**
     * quote
     *
     * Quote a field for usage in a statement.
     */
    abstract function quote($what);

    function nextAlias() {
        // Use alias A1-A9,B1-B9,...
        $alias = chr(65 + (int)($this->alias_num / 9)) . $this->alias_num % 9;
        $this->alias_num++;
        return $alias;
    }
    
    // Statement compilations
    abstract function compileCount(QuerySet $qs);
    abstract function compileSelect(QuerySet $qs);
    abstract function compileUpdate(ModelBase $model);
    abstract function compileInsert(ModelBase $model);
    abstract function compileDelete(ModelBase $model);
    abstract function compileBulkDelete(QuerySet $queryset);
    abstract function compileBulkUpdate(QuerySet $queryset, array $what);
    abstract function inspectTable($table);
}