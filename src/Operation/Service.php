<?php

namespace Phlite\Operation;

interface Service {
    function process($request);
    
    function validate($request);
    function perform($request);
}