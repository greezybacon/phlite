<?php

namespace Phlite\Dispatch;

use Phlite\Request\BaseRequest;

abstract class BaseHandler implements Handler {

    private $middleware;

    /**
     * Launches a new request. This would be the main entry for a Phlite
     * framework handled request.
     */
    function invoke() {
        Signal::connect('php.fatal', array($this, '__onShutdown'));
        Signal::connect('php.exception', array($this, '__onUnhandledException'));

        $this->loadMiddleware();

        Signal::send('request.start', $this);
        try {
            $request = $this->getRequest();
            $response = $this->getResponse($request);
        }
        catch (UnicodeException $ex) {
            $response = HttpResponseBadRequest();
        }

        $response->setHandler($this);
        return $response;
    }

    function loadMiddleware() {
        $this->middleware = new MiddlewareList();
        foreach ($this->config->middleware_classes as $c) {
            $this->middleware[] = new $c();
        }
    }

    function getRequest() {
        return new BaseRequest();
    }

    function getResponse($request) {
        $resp = $this->middleware->processRequest($this);
        if ($resp instanceof Response)
            return $resp;

        // Resolve the view for this request
        $disp = new Dispatcher();
        $view = $disp->resolve($request->getPath());
        if (!$view)
            // TODO: Server some error page
            $view = ErrorView::lookup(404, 'Broken link');

        $this->middleware->processView($request, $view);

        $response = $view->invoke();
        if ($response instanceof TemplateResponse) {
            // Todo: Load context processors
            $this->middleware->processTemplateResponse($request, $response);
            $response = $response->render($request);
        }

        $this->middleware->reverse()->processResponse($request, $response);

        return $response;
    }

    function getPathInfo() {
        if (isset($_SERVER['PATH_INFO']))
            return $_SERVER['PATH_INFO'];

        if (isset($_SERVER['ORIG_PATH_INFO']))
            return $_SERVER['ORIG_PATH_INFO'];

        // TODO: conruct possible path info.

        return null;
    }

    function __onShutdown() {
        $error = error_get_last();
        if ($error !== null) {
            $this->middleware->reverse()
                ->processException($this, $error);
        }
    }

    function __onUnhandledException($ex) {
        $this->middleware->reverse()
            ->processException($this, $ex);
    }
}
