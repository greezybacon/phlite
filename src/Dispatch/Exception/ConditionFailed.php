<?php

namespace Phlite\Dispatch\Exception;

/**
 * A dispatch condition failed. For instance the wrong HTTP verb was
 * requested or a required query arg was missing, Accept-Language
 * couldn't be satisifed. Ultimately, a different endpoint in the dispatch
 * tree will have to handle the request.
 */
class ConditionFailed extends DispatchException {}