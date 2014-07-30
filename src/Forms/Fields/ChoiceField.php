<?php

namespace Phlite\Forms\Fields;

use Phlite\Forms\Field;

class ChoiceField extends Field {
    static $widget = 'ChoicesWidget';

    function getConfigurationOptions() {
        return array(
            'choices'  =>  new TextareaField(array(
                'id'=>1, 'label'=>'Choices', 'required'=>false, 'default'=>'',
                'hint'=>'List choices, one per line. To protect against
                spelling changes, specify key:value names to preserve
                entries if the list item names change',
                'configuration'=>array('html'=>false)
            )),
            'default' => new TextboxField(array(
                'id'=>3, 'label'=>'Default', 'required'=>false, 'default'=>'',
                'hint'=>'(Enter a key). Value selected from the list initially',
                'configuration'=>array('size'=>20, 'length'=>40),
            )),
            'prompt' => new TextboxField(array(
                'id'=>2, 'label'=>'Prompt', 'required'=>false, 'default'=>'',
                'hint'=>'Leading text shown before a value is selected',
                'configuration'=>array('size'=>40, 'length'=>40),
            )),
        );
    }

    function parse($value) {
        if (is_numeric($value))
            return $value;
        foreach ($this->getChoices() as $k=>$v)
            if (strcasecmp($value, $k) === 0)
                return $k;
    }

    function toString($value) {
        $choices = $this->getChoices();
        if (isset($choices[$value]))
            return $choices[$value];
        else
            return $choices[$this->get('default')];
    }

    function getChoices() {
        if ($this->_choices === null) {
            // Allow choices to be set in this->ht (for configurationOptions)
            $this->_choices = $this->get('choices');
            if (!$this->_choices) {
                $this->_choices = array();
                $config = $this->getConfiguration();
                $choices = explode("\n", $config['choices']);
                foreach ($choices as $choice) {
                    // Allow choices to be key: value
                    list($key, $val) = explode(':', $choice);
                    if ($val == null)
                        $val = $key;
                    $this->_choices[trim($key)] = trim($val);
                }
            }
        }
        return $this->_choices;
     }
}
