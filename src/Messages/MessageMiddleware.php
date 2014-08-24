<?php

namespace Phlite\Messages;

use Phlite\Messages\Messages;
use Phlite\Project;
use Phlite\Request\Middleware;

class MessageMiddleware extends Middleware {

    function processRequest($request) {
        // TODO: Load message storage backend and add to request
        $bk = $request->getSettings()->get('MESSAGE_STORAGE',
            'Phlite\Messages\Storage\SessionStorage');
        $request->messages = new $bk($request);
    }

    function processTemplateResponse($request, $response) {
        $context = $response->context;
        $context['messages'] = Messages::getMessages($request);
    }

    /**
     * Updates the storage backend and commits the messages added to the
     * request instance
     */
    function processResponse($request, $response) {
        if (isset($request->messages)) {
            $unsaved = $request->messages->update($response);
            if ($unsaved && $request->getSettings()->get('DEBUG')) {
                throw new RuntimeException(
                    'Not all messages could be saved');
            }
        }
    }
}
