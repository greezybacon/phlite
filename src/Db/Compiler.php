<?php

namespace Phlite\Db;

class SqlCompiler {
    var $options = array();
    var $params = array();
    var $joins = array();
    var $aliases = array();
    var $alias_num = 1;

    static $operators = array(
        'exact' => '%$1s = %$2s'
    );

    function __construct($options=false) {
        if ($options)
            $this->options = array_merge($this->options, $options);
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
     */
    function getField($field, $model, $options=array()) {
        $joins = array();

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
        $crumb = '';
        $alias = $this->quote($model::$meta['table']);

        // Traverse through the parts and establish joins between the tables
        // if the field is joined to a foreign model
        if (count($parts) && isset($model::$meta['joins'][$parts[0]])) {
            // Call pushJoin for each segment in the join path. A new
            // JOIN fragment will need to be emitted and/or cached
            foreach ($parts as $p) {
                $path[] = $p;
                $tip = implode('__', $path);
                $info = $model::$meta['joins'][$p];
                $alias = $this->pushJoin($crumb, $tip, $model, $info);
                // Roll to foreign model
                foreach ($info['constraint'] as $local => $foreign) {
                    list($model, $f) = explode('.', $foreign);
                    if (class_exists($model))
                        break;
                }
                $crumb = $tip;
            }
        }
        if (isset($options['table']) && $options['table'])
            $field = $alias;
        elseif ($alias)
            $field = $alias.'.'.$this->quote($field);
        else
            $field = $this->quote($field);
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
    function pushJoin($tip, $path, $model, $info) {
        // TODO: Build the join statement fragment and return the table
        // alias. The table alias will be useful where the join is used in
        // the WHERE and ORDER BY clauses

        // If the join already exists for the statement-being-compiled, just
        // return the alias being used.
        if (isset($this->joins[$path]))
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
        $this->joins[$path] = array(
            'alias' => $alias,
            'sql'=> $this->compileJoin($tip, $model, $alias, $info),
        );
        return $alias;
    }

    function compileWhere($where, $model) {
        $constraints = array();
        foreach ($where as $constraint) {
            $filter = array();
            foreach ($constraint as $field=>$value) {
                list($field, $op) = $this->getField($field, $model);
                // Allow operators to be callable rather than sprintf
                // strings
                if ($value === null)
                    $filter[] = sprintf('%s IS NULL', $field);
                elseif (is_callable($op))
                    $filter[] = call_user_func($op, $field, $value);
                else
                    $filter[] = sprintf($op, $field, $this->input($value));
            }
            // Multiple constraints here are ANDed together
            $constraints[] = implode(' AND ', $filter);
        }
        // Multiple constrains here are ORed together
        $filter = implode(' OR ', $constraints);
        if (count($constraints) > 1)
            $filter = '(' . $filter . ')';
        return $filter;
    }

    function getParams() {
        return $this->params;
    }

    function getJoins() {
        $sql = '';
        foreach ($this->joins as $j)
            $sql .= $j['sql'];
        return $sql;
    }

    function nextAlias() {
        // Use alias A1-A9,B1-B9,...
        $alias = chr(65 + (int)($this->alias_num / 9)) . $this->alias_num % 9;
        $this->alias_num++;
        return $alias;
    }
}

