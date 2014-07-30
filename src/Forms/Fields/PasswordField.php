<?php

namespace Phlite\Forms\Fields;

use Phlite\Forms\TextboxField;

class PasswordField extends TextboxField {
    static $widget = 'PasswordWidget';

    function to_database($value) {
        return Crypto::encrypt($value, SECRET_SALT, $this->getFormName());
    }

    function to_php($value) {
        return Crypto::decrypt($value, SECRET_SALT, $this->getFormName());
    }
}

