<?php

namespace Phlite\Forms\Fields;

use Phlite\Forms\Field;

class TextboxField extends Field {
    static $widget = 'TextboxWidget';

    function getConfigurationOptions() {
        return array(
            'size'  =>  new TextboxField(array(
                'id'=>1, 'label'=>'Size', 'required'=>false, 'default'=>16,
                    'validator' => 'number')),
            'length' => new TextboxField(array(
                'id'=>2, 'label'=>'Max Length', 'required'=>false, 'default'=>30,
                    'validator' => 'number')),
            'validator' => new ChoiceField(array(
                'id'=>3, 'label'=>'Validator', 'required'=>false, 'default'=>'',
                'choices' => array('phone'=>'Phone Number','email'=>'Email Address',
                    'ip'=>'IP Address', 'number'=>'Number', ''=>'None'))),
            'validator-error' => new TextboxField(array(
                'id'=>4, 'label'=>'Validation Error', 'default'=>'',
                'configuration'=>array('size'=>40, 'length'=>60),
                'hint'=>'Message shown to user if the input does not match the validator')),
            'placeholder' => new TextboxField(array(
                'id'=>5, 'label'=>'Placeholder', 'required'=>false, 'default'=>'',
                'hint'=>'Text shown in before any input from the user',
                'configuration'=>array('size'=>40, 'length'=>40),
            )),
        );
    }

    function validateEntry($value) {
        parent::validateEntry($value);
        $config = $this->getConfiguration();
        $validators = array(
            '' =>       null,
            'email' =>  array(array('Validator', 'is_email'),
                'Enter a valid email address'),
            'phone' =>  array(array('Validator', 'is_phone'),
                'Enter a valid phone number'),
            'ip' =>     array(array('Validator', 'is_ip'),
                'Enter a valid IP address'),
            'number' => array('is_numeric', 'Enter a number')
        );
        // Support configuration forms, as well as GUI-based form fields
        $valid = $this->get('validator');
        if (!$valid) {
            $valid = $config['validator'];
        }
        if (!$value || !isset($validators[$valid]))
            return;
        $func = $validators[$valid];
        $error = $func[1];
        if ($config['validator-error'])
            $error = $config['validator-error'];
        if (is_array($func) && is_callable($func[0]))
            if (!call_user_func($func[0], $value))
                $this->_errors[] = $error;
    }
}
