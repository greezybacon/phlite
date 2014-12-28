<?php

namespace Phlite\Operation;

/**
 * BaseService
 *
 * Services actually perform the work for operations. Operations provide
 * the connection between the dispatcher and the service. The service
 * is used to actually modify an object, using these steps:
 *
 * 1. Verify access (performed by the operation / view)
 * 2. Validate user input (::validate())
 * 3. Update to object (::perform())
 *
 * XXX: Should this be collapsed into Operation?
 */
abstract class BaseService {
    var $object;
    
    function __construct($object=null) {
        $this->object = $object;
    }
}

class TicketCreate
extends BaseService {
    
    function process($request) {
        if ($this->validate($request))
            $this->process($request);
    }
    
    function validate($request) {
        $vars = $request->POST;
    }
    
    function perform($request) {
        
    }
}