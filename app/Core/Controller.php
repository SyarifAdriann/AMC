<?php

namespace App\Core;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\View\View;
use App\Security\CsrfManager;

abstract class Controller
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Validate the CSRF token from the X-CSRF-Token header, form field, or JSON body.
     */
    protected function verifyCsrf(Request $request): bool
    {
        $token = $request->header('X-Csrf-Token');

        if (!is_string($token) || $token === '') {
            $token = $request->input('csrf_token');
        }

        if (!is_string($token) || $token === '') {
            $token = $request->json('csrf_token');
        }

        return $this->app->make(CsrfManager::class)->validate(is_string($token) ? $token : null);
    }

    protected function csrfFailureResponse(): Response
    {
        return Response::json([
            'success' => false,
            'message' => 'Invalid or expired security token. Please refresh the page and try again.',
        ], 403);
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
