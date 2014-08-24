<?php

namespace Phlite\Messages\Storage;

interface StorageBackend extends \IteratorAggregate {

    function setLevel($level);
    function getLevel();

    function update($response);
    function add($level, $message);
}
