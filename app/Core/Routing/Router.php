<?php

namespace App\Core\Routing;

use App\Core\Application;
use App\Core\Http\Request;
use App\Core\Http\Response;
use RuntimeException;

class Router
{
    protected Application $app;

    /**
     * @var array<string, array<int, array<string, mixed>>>
     */
    protected array $routes = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $groupStack = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function add(string $method, string $uri, $action, array $middleware = []): self
    {
        $method = strtoupper($method);
        $attributes = $this->mergeGroupAttributes([
            'uri' => $uri,
            'action' => $action,
            'middleware' => $middleware,
        ]);

        $route = $this->compileRoute($method, $attributes);
        $this->routes[$method][] = $route;

        return $this;
    }

    public function get(string $uri, $action, array $middleware = []): self
    {
        return $this->add('GET', $uri, $action, $middleware);
    }

    public function post(string $uri, $action, array $middleware = []): self
    {
        return $this->add('POST', $uri, $action, $middleware);
    }

    public function put(string $uri, $action, array $middleware = []): self
    {
        return $this->add('PUT', $uri, $action, $middleware);
    }

    public function delete(string $uri, $action, array $middleware = []): self
    {
        return $this->add('DELETE', $uri, $action, $middleware);
    }

    public function match(array $methods, string $uri, $action, array $middleware = []): self
    {
        foreach ($methods as $method) {
            $this->add($method, $uri, $action, $middleware);
        }

        return $this;
    }

    /**
     * Define a route group.
     */
    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $this->normalizeGroupAttributes($attributes);

        $callback($this);

        array_pop($this->groupStack);
    }

    /**
     * Dispatch an incoming request to a route.
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();
        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $route) {
            if (preg_match($route['compiled'], $path, $matches)) {
                $params = $this->extractParameters($route['parameterNames'], $matches);
                return $this->runRoute($route, $request, $params);
            }
        }

        return Response::make('Not Found', 404);
    }

    protected function runRoute(array $route, Request $request, array $params): Response
    {
        $action = $route['action'];
        $destination = function (Request $request) use ($action, $params) {
            $result = $this->callAction($action, $params);

            if ($result instanceof Response) {
                return $result;
            }

            return Response::make((string) $result);
        };

        return $this->runMiddlewarePipeline($route['middleware'], $request, $destination);
    }

    protected function callAction($action, array $params)
    {
        if (is_callable($action)) {
            return $action(...array_values($params));
        }

        if (is_string($action) && strpos($action, '@') !== false) {
            [$class, $method] = explode('@', $action, 2);
            $controller = $this->app->has($class)
                ? $this->app->make($class)
                : new $class($this->app);

            if (!method_exists($controller, $method)) {
                throw new RuntimeException("Controller {$class}::{$method} not found");
            }

            return $controller->{$method}(...array_values($params));
        }

        if (is_array($action) && count($action) === 2) {
            [$class, $method] = $action;
            $controller = $this->app->has($class)
                ? $this->app->make($class)
                : new $class($this->app);

            return $controller->{$method}(...array_values($params));
        }

        throw new RuntimeException('Invalid route action.');
    }

    protected function runMiddlewarePipeline(array $middleware, Request $request, callable $destination): Response
    {
        $pipeline = array_reverse($middleware);
        $next = $destination;

        foreach ($pipeline as $layer) {
            $middlewareCallable = $this->resolveMiddleware($layer);

            $next = function (Request $request) use ($middlewareCallable, $next) {
                return $middlewareCallable($request, $next);
            };
        }

        $response = $next($request);

        if (!$response instanceof Response) {
            $response = Response::make((string) $response);
        }

        return $response;
    }

    protected function resolveMiddleware($middleware): callable
    {
        if (is_callable($middleware)) {
            return $middleware;
        }

        if (is_string($middleware)) {
            $instance = $this->app->has($middleware) ? $this->app->make($middleware) : new $middleware($this->app);

            if (!method_exists($instance, 'handle')) {
                throw new RuntimeException("Middleware {$middleware} must define a handle method.");
            }

            return function (Request $request, callable $next) use ($instance) {
                return $instance->handle($request, $next);
            };
        }

        throw new RuntimeException('Invalid middleware provided.');
    }

    protected function compileRoute(string $method, array $attributes): array
    {
        $uri = '/' . trim($attributes['uri'], '/') ;
        if ($uri === '//') {
            $uri = '/';
        }

        $parameterNames = [];
        $pattern = preg_replace_callback('/\{([^}]+)\}/', function (array $matches) use (&$parameterNames) {
            $parameterNames[] = $matches[1];
            return '(?P<' . $matches[1] . '>[^/]+)';
        }, $uri);

        $compiled = '#^' . $pattern . '$#';

        return [
            'method' => $method,
            'uri' => $uri,
            'compiled' => $compiled,
            'parameterNames' => $parameterNames,
            'action' => $attributes['action'],
            'middleware' => $attributes['middleware'],
        ];
    }

    protected function extractParameters(array $names, array $matches): array
    {
        $params = [];

        foreach ($names as $name) {
            if (isset($matches[$name])) {
                $params[$name] = $matches[$name];
            }
        }

        return $params;
    }

    /**
     * Merge current group attributes onto the route definition.
     */
    protected function mergeGroupAttributes(array $routeAttributes): array
    {
        $uri = $routeAttributes['uri'];
        $middleware = $routeAttributes['middleware'];

        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $uri = rtrim($group['prefix'], '/') . '/' . ltrim($uri, '/');
            }

            if (isset($group['middleware'])) {
                $middleware = array_merge($middleware, (array) $group['middleware']);
            }
        }

        $routeAttributes['uri'] = $uri;
        $routeAttributes['middleware'] = array_values(array_unique($middleware));

        return $routeAttributes;
    }

    protected function normalizeGroupAttributes(array $attributes): array
    {
        $normalized = [];

        if (isset($attributes['prefix'])) {
            $normalized['prefix'] = '/' . trim($attributes['prefix'], '/');
        }

        if (isset($attributes['middleware'])) {
            $normalized['middleware'] = (array) $attributes['middleware'];
        }

        return $normalized;
    }
}

