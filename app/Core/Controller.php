<?php

namespace App\Core;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View\View;

abstract class Controller
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    protected function request(): Request
    {
        if ($this->app->has(Request::class)) {
            return $this->app->make(Request::class);
        }

        $request = Request::capture();
        $this->app->instance(Request::class, $request);

        return $request;
    }

    protected function view(string $template, array $data = [], int $status = 200): Response
    {
        $view = new View($this->app->basePath('resources/views'));
        return Response::make($view->render($template, $data), $status);
    }

    protected function json($data, int $status = 200, array $headers = []): Response
    {
        return Response::json($data, $status, $headers);
    }
}
