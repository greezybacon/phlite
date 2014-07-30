<?php

namespace Phlite\Forms\Widgets;

use Phlite\Forms\Widgets\TextboxWidget;;

class PasswordWidget extends TextboxWidget {
    static $input_type = 'password';

    function parseValue() {
        // Show empty box unless failed POST
        if ($_SERVER['REQUEST_METHOD'] == 'POST'
                && $this->field->getForm()->isValid())
            parent::parseValue();
        else
            $this->value = '';
    }
}
