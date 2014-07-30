<?php

namespace Phlite\Forms;

class Widget {

    function __construct($field) {
        $this->field = $field;
        $this->name = $field->getFormName();
    }

    function parseValue() {
        $this->value = $this->getValue();
        if (!isset($this->value) && is_object($this->field->getAnswer()))
            $this->value = $this->field->getAnswer()->getValue();
        if (!isset($this->value) && $this->field->value)
            $this->value = $this->field->value;
    }

    function getValue() {
        $data = $this->field->getSource();
        // Search for HTML form name first
        if (isset($data[$this->name]))
            return $data[$this->name];
        elseif (isset($data[$this->field->get('name')]))
            return $data[$this->field->get('name')];
        return null;
    }
}
