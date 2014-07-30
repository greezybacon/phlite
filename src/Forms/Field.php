<?php

namespace Phlite\Forms;

class Field {
    static $widget = false;

    var $ht = array(
        'label' => 'Unlabeled',
        'required' => false,
        'default' => false,
        'configuration' => array(),
    );

    var $_form;
    var $_cform;
    var $_clean;
    var $_errors = array();
    var $_widget;
    var $answer;
    var $parent;
    var $presentation_only = false;

    static $types = array(
        'Basic Fields' => array(
            'text'  => array('Short Answer', 'TextboxField'),
            'memo' => array('Long Answer', 'TextareaField'),
            'thread' => array('Thread Entry', 'ThreadEntryField', false),
            'datetime' => array('Date and Time', 'DatetimeField'),
            'phone' => array('Phone Number', 'PhoneField'),
            'bool' => array('Checkbox', 'BooleanField'),
            'choices' => array('Choices', 'ChoiceField'),
            'break' => array('Section Break', 'SectionBreakField'),
        ),
    );
    static $more_types = array();

    function __construct($options=array()) {
        static $uid = 100;
        $this->ht = array_merge($this->ht, $options);
        if (!isset($this->ht['id']))
            $this->ht['id'] = $uid++;
    }

    static function addFieldTypes($group, $callable) {
        static::$more_types[$group] = $callable;
    }

    static function allTypes() {
        if (static::$more_types) {
            foreach (static::$more_types as $group=>$c)
                static::$types[$group] = call_user_func($c);
            static::$more_types = array();
        }
        return static::$types;
    }

    static function getFieldType($type) {
        foreach (static::allTypes() as $group=>$types)
            if (isset($types[$type]))
                return $types[$type];
    }

    function get($what) {
        return $this->ht[$what];
    }

    /**
     * getClean
     *
     * Validates and cleans inputs from POST request. This is performed on a
     * field instance, after a DynamicFormSet / DynamicFormSection is
     * submitted via POST, in order to kick off parsing and validation of
     * user-entered data.
     */
    function getClean() {
        if (!isset($this->_clean)) {
            $this->_clean = (isset($this->value))
                ? $this->value : $this->parse($this->getWidget()->value);
            $this->validateEntry($this->_clean);
        }
        return $this->_clean;
    }
    function reset() {
        $this->_clean = $this->_widget = null;
    }

    function errors() {
        return $this->_errors;
    }
    function addError($message, $field=false) {
        if ($field)
            $this->_errors[$field] = $message;
        else
            $this->_errors[] = $message;
    }

    function isValidEntry() {
        $this->validateEntry();
        return count($this->_errors) == 0;
    }

    /**
     * validateEntry
     *
     * Validates user entry on an instance of the field on a dynamic form.
     * This is called when an instance of this field (like a TextboxField)
     * receives data from the user and that value should be validated.
     *
     * Parameters:
     * $value - (string) input from the user
     */
    function validateEntry($value) {
        if (!$value && count($this->_errors))
            return;

        # Validates a user-input into an instance of this field on a dynamic
        # form
        if ($this->get('required') && !$value && $this->hasData())
            $this->_errors[] = sprintf('%s is a required field', $this->getLabel());

        # Perform declared validators for the field
        if ($vs = $this->get('validators')) {
            if (is_array($vs)) {
                foreach ($vs as $validator)
                    if (is_callable($validator))
                        $validator($this, $value);
            }
            elseif (is_callable($vs))
                $vs($this, $value);
        }
    }

    /**
     * parse
     *
     * Used to transform user-submitted data to a PHP value. This value is
     * not yet considered valid. The ::validateEntry() method will be called
     * on the value to determine if the entry is valid. Therefore, if the
     * data is clearly invalid, return something like NULL that can easily
     * be deemed invalid in ::validateEntry(), however, can still produce a
     * useful error message indicating what is wrong with the input.
     */
    function parse($value) {
        return trim($value);
    }

    /**
     * to_php
     *
     * Transforms the data from the value stored in the database to a PHP
     * value. The ::to_database() method is used to produce the database
     * valse, so this method is the compliment to ::to_database().
     *
     * Parameters:
     * $value - (string or null) database representation of the field's
     *      content
     */
    function to_php($value) {
        return $value;
    }

    /**
     * to_database
     *
     * Determines the value to be stored in the database. The database
     * backend for all fields is a text field, so this method should return
     * a text value or NULL to represent the value of the field. The
     * ::to_php() method will convert this value back to PHP.
     *
     * Paremeters:
     * $value - PHP value of the field's content
     */
    function to_database($value) {
        return $value;
    }

    /**
     * toString
     *
     * Converts the PHP value created in ::parse() or ::to_php() to a
     * pretty-printed value to show to the user. This is especially useful
     * for something like dates which are stored considerably different in
     * the database from their respective human-friendly versions.
     * Furthermore, this method allows for internationalization and
     * localization.
     *
     * Parametes:
     * $value - PHP value of the field's content
     */
    function toString($value) {
        return (string) $value;
    }

    /**
     * Returns an HTML friendly value for the data in the field.
     */
    function display($value) {
        return Format::htmlchars($this->toString($value));
    }

    /**
     * Returns a value suitable for exporting to a foreign system. Mostly
     * useful for things like dates and phone numbers which should be
     * formatted using a standard when exported
     */
    function export($value) {
        return $this->toString($value);
    }

    function getLabel() { return $this->get('label'); }

    /**
     * getImpl
     *
     * Magic method that will return an implementation instance of this
     * field based on the simple text value of the 'type' value of this
     * field instance. The list of registered fields is determined by the
     * global get_dynamic_field_types() function. The data from this model
     * will be used to initialize the returned instance.
     *
     * For instance, if the value of this field is 'text', a TextField
     * instance will be returned.
     */
    function getImpl($parent=null) {
        // Allow registration with ::addFieldTypes and delayed calling
        $type = static::getFieldType($this->get('type'));
        $clazz = $type[1];
        $inst = new $clazz($this->ht);
        $inst->parent = $parent;
        $inst->setForm($this->_form);
        return $inst;
    }

    function __call($what, $args) {
        // XXX: Throw exception if $this->parent is not set
        if (!$this->parent)
            throw new Exception($what.': Call to undefined function');
        // BEWARE: DynamicFormField has a __call() which will create a new
        //      FormField instance and invoke __call() on it or bounce
        //      immediately back
        return call_user_func_array(
            array($this->parent, $what), $args);
    }

    function getAnswer() { return $this->answer; }
    function setAnswer($ans) { $this->answer = $ans; }

    function getFormName() {
        if (is_numeric($this->get('id')))
            return substr(md5(
                session_id() . '-field-id-'.$this->get('id')), -16);
        else
            return $this->get('id');
    }

    function setForm($form) {
        $this->_form = $form;
    }
    function getForm() {
        return $this->_form;
    }
    /**
     * Returns the data source for this field. If created from a form, the
     * data source from the form is returned. Otherwise, if the request is a
     * POST, then _POST is returned.
     */
    function getSource() {
        if ($this->_form)
            return $this->_form->getSource();
        elseif ($_SERVER['REQUEST_METHOD'] == 'POST')
            return $_POST;
        else
            return array();
    }

    function render($mode=null) {
        $this->getWidget()->render($mode);
    }

    function renderExtras($mode=null) {
        return;
    }

    function getConfigurationOptions() {
        return array();
    }

    /**
     * getConfiguration
     *
     * Loads configuration information from database into hashtable format.
     * Also, the defaults from ::getConfigurationOptions() are integrated
     * into the database-backed options, so that if options have not yet
     * been set or a new option has been added and not saved for this field,
     * the default value will be reflected in the returned configuration.
     */
    function getConfiguration() {
        if (!$this->_config) {
            $this->_config = $this->get('configuration');
            if (is_string($this->_config))
                $this->_config = JsonDataParser::parse($this->_config);
            elseif (!$this->_config)
                $this->_config = array();
            foreach ($this->getConfigurationOptions() as $name=>$field)
                if (!isset($this->_config[$name]))
                    $this->_config[$name] = $field->get('default');
        }
        return $this->_config;
    }

    /**
     * If the [Config] button should be shown to allow for the configuration
     * of this field
     */
    function isConfigurable() {
        return true;
    }

    /**
     * Field type is changeable in the admin interface
     */
    function isChangeable() {
        return true;
    }

    /**
     * Field does not contain data that should be saved to the database. Ie.
     * non data fields like section headers
     */
    function hasData() {
        return true;
    }

    /**
     * Returns true if the field/widget should be rendered as an entire
     * block in the target form.
     */
    function isBlockLevel() {
        return false;
    }

    /**
     * Fields should not be saved with the dynamic data. It is assumed that
     * some static processing will store the data elsewhere.
     */
    function isPresentationOnly() {
        return $this->presentation_only;
    }

    /**
     * Indicates if the field places data in the `value_id` column. This
     * is currently used by the materialized view system
     */
    function hasIdValue() {
        return false;
    }

    function getConfigurationForm() {
        if (!$this->_cform) {
            $type = static::getFieldType($this->get('type'));
            $clazz = $type[1];
            $T = new $clazz();
            $this->_cform = $T->getConfigurationOptions();
        }
        return $this->_cform;
    }

    function getWidget() {
        if (!static::$widget)
            throw new Exception('Widget not defined for this field');
        if (!isset($this->_widget)) {
            $wc = $this->get('widget') ? $this->get('widget') : static::$widget;
            $this->_widget = new $wc($this);
            $this->_widget->parseValue();
        }
        return $this->_widget;
    }
}
