<?php

namespace Phlite\Operation;

use Phlite\Exception\ProgrammingError;
use Phlite\Security\Features\CsrfProtected;

/**
 * BaseOperation
 *
 * An operation is used like a view in Route tables. It is used instead to
 * provide write access to objects whereas views are primarily used for
 * read only access to objects. Usually, an operation is used to create or
 * modify an object, and one of two things happen. If the current user has
 * privilege to perform the operation, and the operation succeeds, then the
 * read only view for the object is servered. However, if the user does not
 * have privilege to perform the operation, then either an access denied
 * page or a redirect to a login page is served instead. Furthermore, if
 * the operation can be performed by is not successful, a fallback page
 * which might show a listing of similar objects might be served and or a
 * warning or error message might be appended to the current messages list
 * for display in the next page view. The Policy object is used to control
 * such access to objects.
 *
 * Because Operations extend from Views, the ususal workflow might include
 * dispatching a GET request to an Operation to serve an appropriate page
 * to ask the user for information (such as editing an object). Thereafter,
 * a POST request could be sent to the same operation which would recheck
 * the user's privileges and perform the Operation. 
 *
 * For direct operations such as DELETE, the Operation might be served
 * directly.
 */
abstract class BaseOperation
extends BaseView
implements CsrfProtected {

}