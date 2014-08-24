<?php

namespace Phlite\Auth\Model;

/**
 * Fields:
 * username (string) - 
 * name (string) -
 * password (\Phlite\Auth\Model\PasswordField)
 * permissions (rel:many:\Phlite\Auth\Model\Permission.id)
 * group (rel:\Phlite\Auth\Model\UserGroup.id)
 */
trait User {
    use \Phlite\Db\Fieldset;
    
    // Add getters, setters, and validation stuff here
}

trait UserModel {
    use \Phlite\Db\ModelBase,
        \Phlite\Auth\Model\User;
    
    // Add other model-related methods (search, save, etc.)
}

class User 
    implements Model {
    use UserModel;
    
    // Business logic goes here
}
