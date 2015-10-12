<?php

namespace Phlite\Db;

/**
 * Utility class to allow several operations to be performed atomically in a
 * transaction at the database layer. Instead of using the ActiveRecord
 * patttern and saving the objects directly, objects are added to a
 * transaction. The transaction can then be rollled-back, or committed.
 *
 * Transactions across multiple databases and connections are supported. If
 * any transaction fails to commit, all transactions not yet committed are
 * rolled back. If the database backends support two-phase commits, then the
 * transactions are only committed if all connections committed
 * successfully.
 *
 * Transaction objects should not be created directly. Use the manager
 * instance to get access to a current transaction.
 */
class TransactionCoordinator {
    const FLAG_AUTOFLUSH    = 0x0001;
    const FLAG_RETRY_COMMIT = 0x0002;

    const TYPE_UPDATE = 1;
    const TYPE_DELETE = 2;

    protected $manager;
    protected $mode;
    protected $dirty = array();
    protected $backends = array();
    var $started = false;

    function __construct(Manager $manager, $flags=0) {
        $this->manager = $manager;
        $this->setFlag($flags);
    }

    /**
     * Set the mode of the transaction. 
     *
     * FLAG_AUTOFLUSH 
     *      Send updates and deletes to the database immediately. The save
     *      callback is invoked when the object is added to the transaction
     *      and updates to the same object are not deduplicated.
     *
     * FLAG_RETRY_COMMIT 
     *      Retry the commit one time if the commit fails
     */
    function setFlag($mode) {
        $this->mode |= $mode;
    }

    function add(Model\ModelBase $model, $callback, $args=null) {
        $this->dirtifiy(self::TYPE_UDPATE, $model, $callback, $args);
    }

    function delete(Model\ModelBase $model) {
        $this->dirtifiy(self::TYPE_DELETE, $model, $callback, $args);
    }

    protected function dirtify($type, $model, $callback, $args) {
        $this->captureBackend($model);

        // Add to dirty bucket
        $key = sprintf('%s.%s',
            $model::$meta->model, implode('.', $model->pk));

        // No more changes after marked for deletion
        if (isset($this->dirty[$key])) {
            list($type) = $this->dirty[$key];
            if ($type == self::TYPE_DELETE)
                // No change necessary as the object will be deleted
                return;
        }

        $this->dirty[$key] = array($type, $model, $callback, $args);

        if ($this->mode & self::FLAG_AUTOFLUSH)
            $this->flush();
    }

    protected function captureBackend($model) {
        // Capture the number of backends we're dealing with
        $backend = $this->manager->getConnection($model); 
        $bkkey = spl_object_hash($backend);

        if ($this->started && !isset($this->backend[$bkkey])) {
            $this->rollback();
            throw new Exception\OrmError('Unable to add a backed in the middle of a transaction');
        }

        $this->backends[$bkkey] = $backend;
    }

    // Start a transaction on the database backends if not yet started
    protected function start() {
        if ($this->started)
            return;

        $distributed = count($this->backends) > 1;
        foreach ($this->backends as $bk) {
            if ($distributed)   $bk->startDistributed();
            else                $bk->beginTransaction();
        }

        // Assume that the transaction starters would throw an exception if
        // unable to start.
        $this->started = true;
    }

    /**
     * Send all dirty records to the database. This does not imply a commit,
     * it just syncs the underlying databases and calls the save callbacks
     * for the models. It does, however, imply starting a transaction if one
     * has not yet been started.
     */
    function flush() {
        $this->start();
        foreach ($this->dirty as $M) {
            list($type, $model, $cbk, $args) = $M;
            if ($type === self::TYPE_UPDATE)
                $this->manager->_save($model, $cbk, $args);
            elseif ($type === self::TYPE_DELETE)
                $this->manager->_delete($model, $cbk, $args);
        }
        $this->dirty = array();
    }

    function commit($retry=true) {
        $this->flush();

        $distributed = count($this->backends) > 1;
        if ($distributed) {
            $success = true;
            foreach ($this->backends as $bk) {
                if (!($success &= $bk->tryCommit()))
                    break;
            }
            foreach ($this->backends as $bk) {
                if ($success)   $bk->finishCommit();
                else            $bk->undoCommit();
            }
            if (!$success) {
                // TODO: Attempt retry if configured

                // An exception is necessary here because the callbacks for
                // the model updates have already been executed.
                throw new Exception\OrmError('Distributed transaction commit failed');
            }
            return $success;
        }

        // NOTE: There's only one backend here, but it's in a list
        foreach ($this->backends as $bk) {
            return $bk->commit();
            // Rollback if unsuccessful?
        }
    }

    function rollback() {
        // Anything currently dirty is no longer dirty
        foreach ($this->dirty as $M) {
            list($type, $model) = $M;
            $model->__rollback();
        }

        if (!$this->started)
            // Yay! nothing to do at the database layer
            return true;

        $distributed = count($this->backends) > 1;
        if ($distributed) {
            // TODO: Abandon distributed transactions
        }

        // NOTE: There's only one backend here, but it's in a list
        foreach ($this->backends as $bk) {
            return $bk->rollback();
        }
    }
}
