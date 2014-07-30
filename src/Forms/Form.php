<?php

namespace Phlite\Forms;

/**
 * Form template, used for designing the custom form and for entering custom
 * data for a ticket
 */
class Form {
    var $fields = array();
    var $title = 'Unnamed';
    var $instructions = '';

    var $_errors = null;
    var $_source = false;

    function Form() {
        call_user_func_array(array($this, '__construct'), func_get_args());
    }
    function __construct($fields=array(), $source=null, $options=array()) {
        $this->fields = $fields;
        foreach ($fields as $f)
            $f->setForm($this);
        if (isset($options['title']))
            $this->title = $options['title'];
        if (isset($options['instructions']))
            $this->instructions = $options['instructions'];
        // Use POST data if source was not specified
        $this->_source = ($source) ? $source : $_POST;
    }
    function data($source) {
        foreach ($this->fields as $name=>$f)
            if (isset($source[$name]))
                $f->value = $source[$name];
    }

    function getFields() {
        return $this->fields;
    }

    function getField($name) {
        $fields = $this->getFields();
        foreach($fields as $f)
            if(!strcasecmp($f->get('name'), $name))
                return $f;
        if (isset($fields[$name]))
            return $fields[$name];
    }

    function getTitle() { return $this->title; }
    function getInstructions() { return $this->instructions; }
    function getSource() { return $this->_source; }

    /**
     * Validate the form and indicate if there no errors.
     *
     * Parameters:
     * $filter - (callback) function to receive each field and return
     *      boolean true if the field's errors are significant
     */
    function isValid($include=false) {
        if (!isset($this->_errors)) {
            $this->_errors = array();
            $this->getClean();
            foreach ($this->getFields() as $field)
                if ($field->errors() && (!$include || $include($field)))
                    $this->_errors[$field->get('id')] = $field->errors();
        }
        return !$this->_errors;
    }

    function getClean() {
        if (!$this->_clean) {
            $this->_clean = array();
            foreach ($this->getFields() as $key=>$field) {
                if (!$field->hasData())
                    continue;
                $this->_clean[$key] = $this->_clean[$field->get('name')]
                    = $field->getClean();
            }
            unset($this->_clean[""]);
        }
        return $this->_clean;
    }

    function errors() {
        return $this->_errors;
    }

    function render($staff=true, $title=false, $instructions=false) {
        if ($title)
            $this->title = $title;
        if ($instructions)
            $this->instructions = $instructions;
        $form = $this;
        if ($staff)
            include(STAFFINC_DIR . 'templates/dynamic-form.tmpl.php');
        else
            include(CLIENTINC_DIR . 'templates/dynamic-form.tmpl.php');
    }
}
