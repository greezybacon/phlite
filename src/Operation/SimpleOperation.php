<?php

class SimpleOperation
extends BaseOperation {

    /**
     * $service
     *
     * Service used to perform the operation. This allows the operation to
     * be kept separate from the service which is used to perform the
     * operation.
     */
    static $service = false;
    
    /**
     * $model
     *
     * Model to be operated on by this operation. Useful if a PK argument can
     * be passed to the view functions (post, put, delete). If so, then the
     * model can be looked up and operated on commonly using the common methods
     * defined in this class.
     */
    static $model = false;
    
    /**
     * $form
     *
     * Optional form class which is used to operate on the object. This can
     * also be specified by the model itself in the $meta data in the 'form'
     * key
     */
    static $form = false;
    
    /**
     * getObject
     *
     * This method shoudl be defined by the operation subclass and should
     * retrieve the object to be modified by the operation. That object
     * is then handed off to the service to perform the actual operation
     */
    function getObject($pk) {
        if (!static::$object) {
            return null;
        }
        $O = static::$object::lookup($pk);
        if (!$O) {
            throw new Http\Exception\Http404();
        }
    }
    
    function getOrCreate($request, $pk=false) {
        if (!static::$model)
            return null;
        
        if ($pk) {
            $O = $this->getObject($pk);
        }
        // Create from the form associated with this operation
        elseif (static::$form) {
            if (!is_subclass_of(static::$form, 'Form'))
                throw new ProgrammingError(
                    'Operation form must extends from Phlite\Forms\Form');
            $F = new static::$form($request->POST);
            $O = static::$model::create($F->asArray());
        }
        // Use the model form if specified, or the overridden
        // ::createFromRequest method
        else {
            $O = static::$model::createFromRequest($request->POST);
        }
        return $O;
    }
    
    function post($request, $pk) {
        if (!($O = static::getOrCreate($request, $pk)))
            throw new ProgrammingError('Cannot find object on which to operate');
        
        if (static::$service) {
            $S = new static::$service($this->getObject($pk));
            $S->process($request);
        }
        
        // Attempt to use included form
        elseif (static::$form) {
            $F = new static::$form($request->POST);
            foreach ($F->asArray() as $field=>$value) {
                $O->set($field, $value);
            }
            if (!$O->save())
                throw new Exception\OperationFailed();
        }
        // Can't really do this generically
        else {
            throw new \Exception();
        }
    }
    
    function put($request) {
        $O = static::getOrCreate($request);
        if (!$O->save())
            throw new Exception\OperationFailed();
    }
    
    function delete($request, $pk) {
        $O = $this->getObject($pk);
        if (static::$service) {
            $S = new static::$service($O);
            $S->process($request);
        }
        elseif ($O) {
            if (!$O->delete())
                throw new Exception\OperationFailed();
        }
    }
}