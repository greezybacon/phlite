<?php

namespace Phlite\Forms\Fields;

use Phlite\Forms\Field;

class TextareaField extends Field {
    static $widget = 'TextareaWidget';

    function getConfigurationOptions() {
        return array(
            'cols'  =>  new TextboxField(array(
                'id'=>1, 'label'=>'Width (chars)', 'required'=>true, 'default'=>40)),
            'rows'  =>  new TextboxField(array(
                'id'=>2, 'label'=>'Height (rows)', 'required'=>false, 'default'=>4)),
            'length' => new TextboxField(array(
                'id'=>3, 'label'=>'Max Length', 'required'=>false, 'default'=>0)),
            'html' => new BooleanField(array(
                'id'=>4, 'label'=>'HTML', 'required'=>false, 'default'=>true,
                'configuration'=>array('desc'=>'Allow HTML input in this box'))),
            'placeholder' => new TextboxField(array(
                'id'=>5, 'label'=>'Placeholder', 'required'=>false, 'default'=>'',
                'hint'=>'Text shown in before any input from the user',
                'configuration'=>array('size'=>40, 'length'=>40),
            )),
        );
    }

    function display($value) {
        $config = $this->getConfiguration();
        if ($config['html'])
            return Format::safe_html($value);
        else
            return Format::htmlchars($value);
    }
}
