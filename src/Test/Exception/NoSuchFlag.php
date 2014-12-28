<?php

namespace Phlite\Test\Exception;

/**
 * NoSuchFlag
 *
 * Used with the DocTest suite for flags used in doc tests given in API
 * documentation. This exception is thrown by the test evaluator if a
 * flag declared in a doc test does not exist when the test is parsed
 * and evaluated.
 */
class NoSuchFlag extends Exception {
}