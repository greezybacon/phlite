<?php

namespace Phlite\Dispatch;

use Phlite\Http\Exception\Http404;
use Phlite\Http\Exception\HttpException;
use Phlite\Project;
use Phlite\Request\Request;
use Phlite\Request\Response;
use Phlite\Request\TemplateResponse;
use Phlite\Signal;
use Phlite\View\ErrorView;

abstract class BaseHandler implements Handler {

    protected $middleware;
    protected $project;
    
    function __construct(Project $project) {
        $this->project = $project;
        Signal::connectErrors();
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
        catch (\Exception $ex) {
            // TODO: Handle unhandled exception here
            return $this->middleware->reverse()->processException($request, $ex);
        }

        if ($response) {
            $response->setHandler($this);
            return $response->output($request);
        }
    }

    function loadMiddleware() {
        $this->middleware = Project::getCurrent()->getMiddleware();
    }

    function getRequest() {
        return new Request($this);
    }

    function getResponse($request) {
        $resp = $this->middleware->processRequest($request);
        if ($resp instanceof Response)
            return $resp;

        // Resolve the view for this request
        $disp = $this->project->getDispatcher();
        $request->setDispatcher($disp);
        $view = $disp->resolve($request->getPath());
        
        // Handle 404 errors
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

        try {
            return $view($request);
        }
        catch (\Exception $ex) {
            return $this->middleware->reverse()->processException($request, $ex);
        }
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

    function __onShutdown($obj, $info) {
        if ($error !== null && $info['type'] == E_ERROR) {
             $this->middleware->reverse()
                ->processException($this, $error);
        }
    }

    function __onUnhandledException($ex) {
        $this->middleware->reverse()
            ->processException($this, $ex);
    }
}
