<?php

namespace Phlite\Request\Middleware;

use Phlite\Db;
use Phlite\Request\Middleware;

class DbMiddleware extends Middleware {

    function processRequest($request) {
        $dbs = array();
        foreach ($request->getSettings()->get('DATABASES', []) as $key=>$info) {
            $dbs[$key] = new Db\Engine($info);
        }
        $request->databases = $dbs;
        if (isset($dbs['default']))
            $request->db = $dbs['default'];
    }

    function processException($request, $exception) {
        // TODO: Rollback transaction in progress
    }
}
