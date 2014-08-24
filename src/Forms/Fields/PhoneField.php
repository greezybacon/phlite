<?php

namespace Phlite\Forms\Fields;

use Phlite\Forms\Field;

class PhoneField extends Field {
    static $widget = 'PhoneNumberWidget';

    function getConfigurationOptions() {
        return array(
            'ext' => new BooleanField(array(
                'label'=>'Extension', 'default'=>true,
                'configuration'=>array(
                    'desc'=>'Add a separate field for the extension',
                ),
            )),
            'digits' => new TextboxField(array(
                'label'=>'Minimum length', 'default'=>7,
                'hint'=>'Fewest digits allowed in a valid phone number',
                'configuration'=>array('validator'=>'number', 'size'=>5),
            )),
            'format' => new ChoiceField(array(
                'label'=>'Display format', 'default'=>'us',
                'choices'=>array(''=>'-- Unformatted --',
                    'us'=>'United States'),
            )),
        );
    }

    function validateEntry($value) {
        parent::validateEntry($value);
        $config = $this->getConfiguration();
        # Run validator against $this->value for email type
        list($phone, $ext) = explode("X", $value, 2);
        if ($phone && (
                !is_numeric($phone) ||
                strlen($phone) < $config['digits']))
            $this->_errors[] = "Enter a valid phone number";
        if ($ext && $config['ext']) {
            if (!is_numeric($ext))
                $this->_errors[] = "Enter a valid phone extension";
            elseif (!$phone)
                $this->_errors[] = "Enter a phone number for the extension";
        }
    }

    function parse($value) {
        // NOTE: Value may have a legitimate 'X' to separate the number and
        // extension parts. Don't remove the 'X'
        $digits = preg_replace('/[^\dX]/', '', $value);
        return $digits ?: $value;
    }

    function toString($value) {
        $config = $this->getConfiguration();
        list($phone, $ext) = explode("X", $value, 2);
        switch ($config['format']) {
        case 'us':
            $phone = Format::phone($phone);
            break;
        }
        if ($ext)
            $phone.=" x$ext";
        return $phone;
    }
}
