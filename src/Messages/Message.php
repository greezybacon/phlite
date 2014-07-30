<?php

namespace Phlite\Messages;

interface Message {

    function getTags();
    function getLevel();
    function __toString();
}
