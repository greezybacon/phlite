<?php

namespace Phlite\Db;

interface DistributedTransaction {
    /**
     * Attempts to commit the distributed transaction locally and returns
     * TRUE or FALSE if able to successfully commit
     */
    function tryCommit();

    // Second phase commit / rollback
    function undoCommit();
    function finishCommit();
}
