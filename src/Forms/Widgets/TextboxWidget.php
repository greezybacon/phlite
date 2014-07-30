<?php

namespace Phlite\Forms;

use Phlite\Forms\Widget;

class TextboxWidget extends Widget {
    static $input_type = 'text';

    function render() {
        $config = $this->field->getConfiguration();
        if (isset($config['size']))
            $size = "size=\"{$config['size']}\"";
        if (isset($config['length']))
            $maxlength = "maxlength=\"{$config['length']}\"";
        if (isset($config['classes']))
            $classes = 'class="'.$config['classes'].'"';
        if (isset($config['autocomplete']))
            $autocomplete = 'autocomplete="'.($config['autocomplete']?'on':'off').'"';
        ?>
        <span style="display:inline-block">
        <input type="<?php echo static::$input_type; ?>"
            id="<?php echo $this->name; ?>"
            <?php echo $size . " " . $maxlength; ?>
            <?php echo $classes.' '.$autocomplete
                .' placeholder="'.$config['placeholder'].'"'; ?>
            name="<?php echo $this->name; ?>"
            value="<?php echo Format::htmlchars($this->value); ?>"/>
        </span>
        <?php
    }
}
