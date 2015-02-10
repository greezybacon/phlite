<?php

namespace Phlite\Dispatch;

use Phlite\Http\Exception\Http404;
use Phlite\Http\Exception\HttpException;
use Phlite\Project;
use Phlite\Request\MiddlewareList;
use Phlite\Request\Request;
use Phlite\Request\TemplateResponse;
use Phlite\Signal;
use Phlite\View\ErrorView;

abstract class BaseHandler implements Handler {

    protected $middleware;
    protected $project;
    
    function __construct(Project $project) {
        $this->project = $project;
    }

    /**
     * Launches a new request. This would be the main entry for a Phlite
     * framework handled request.
     */
    function __invoke() {
        Signal::connect('php.fatal', array($this, '__onShutdown'));
        Signal::connect('php.exception', array($this, '__onUnhandledException'));

        $this->loadMiddleware();

        Signal::send('request.start', $this);
        try {
            $request = $this->getRequest();
            $response = $this->getResponse($request);
            $response = $this->processResponse($request, $response);
        }
        catch (UnicodeException $ex) {
            $response = new HttpResponseBadRequest();
        }
        catch (HttpException $ex) {
            $response = $ex->getResponse($request);
        }

        $response->setHandler($this);
        return $response->output();
    }

    function loadMiddleware() {
        $this->middleware = new MiddlewareList();
        foreach ($this->project->getSettings()->get('MIDDLEWARE_CLASSES', []) as $c) {
            $this->middleware[] = new $c();
        }
    }

    function getRequest() {
        return new Request($this);
    }

    function getResponse($request) {
        $resp = $this->middleware->processRequest($request);
        if ($resp instanceof Response)
            return $resp;

        // Resolve the view for this request
        $disp = new Dispatcher($this->project->getUrls());
        $view = $disp->resolve($request->getPath());
        if (!$view) {
            // TODO: Serve some error page
            throw new Http404('Broken link');
        }
        elseif ($view instanceof Policy) {
            // The Policy is allowed to return an alternate view or throw 
            // and HttpRedirect exception if the Policy cannot be currently
            // met and another view should be rendered.
            $V = $view->checkPolicy($request);
            if ($V instanceof View) {
                $view = $V;
            }
            elseif ($V instanceof Response) {
                return $V;
            }
        }
        $this->middleware->processView($request, $view);
        
        return $view($request);
    }
    
    function processResponse($request, $response) {
        if ($response instanceof TemplateResponse) {
            $this->middleware->processTemplateResponse($request, $response);
            $response = $response->render($request);
        }

        $this->middleware->reverse()->processResponse($request, $response);
        return $response;
    }
    
    function getProject() {
        return $this->project;
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
