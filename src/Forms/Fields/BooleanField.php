<?php

namespace Phlite\Forms\Fields;

use Phlite\Forms\Field;

class BooleanField extends Field {
    static $widget = 'CheckboxWidget';

    function getConfigurationOptions() {
        return array(
            'desc' => new TextareaField(array(
                'id'=>1, 'label'=>'Description', 'required'=>false, 'default'=>'',
                'hint'=>'Text shown inline with the widget',
                'configuration'=>array('rows'=>2)))
        );
    }

    function to_database($value) {
        return ($value) ? '1' : '0';
    }

    function parse($value) {
        return $this->to_php($value);
    }
    function to_php($value) {
        return $value ? true : false;
    }

    function toString($value) {
        return ($value) ? 'Yes' : 'No';
    }
}
