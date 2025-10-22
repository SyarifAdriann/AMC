<?php

namespace App\Core\Http;

class Request
{
    protected string $method;
    protected string $uri;
    protected array $query;
    protected array $body;
    protected array $server;
    protected array $files;
    protected array $cookies;
    protected array $headers;
    protected ?string $basePath = null;

    public static function capture(): self
    {
        return new self(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $_SERVER['REQUEST_URI'] ?? '/',
            $_GET,
            $_POST,
            $_SERVER,
            $_FILES,
            $_COOKIE
        );
    }

    public function __construct(
        string $method,
        string $uri,
        array $query = [],
        array $body = [],
        array $server = [],
        array $files = [],
        array $cookies = []
    ) {
        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->query = $query;
        $this->body = $body;
        $this->server = $server;
        $this->files = $files;
        $this->cookies = $cookies;
        $this->headers = $this->gatherHeaders($server);
    }

    protected function gatherHeaders(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            }
        }

        if (isset($server['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $server['CONTENT_TYPE'];
        }

        if (isset($server['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = $server['CONTENT_LENGTH'];
        }

        return $headers;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function path(): string
    {
        $path = parse_url($this->uri, PHP_URL_PATH);

        if (!empty($path)) {
            return $this->normalizePath($path);
        }

        $script = $this->server['SCRIPT_NAME'] ?? $this->server['PHP_SELF'] ?? null;
        if (!empty($script)) {
            return $this->normalizePath($script);
        }

        return '/';
    }

    protected function normalizePath(string $path): string
    {
        $path = $this->stripBasePath($path);

        if ($path === '' || $path === '/') {
            return '/';
        }

        return '/' . ltrim($path, '/');
    }

    protected function stripBasePath(string $path): string
    {
        $base = $this->determineBasePath();

        if ($base !== '' && strpos($path, $base) === 0) {
            $path = substr($path, strlen($base));
        }

        return $path;
    }

    protected function determineBasePath(): string
    {
        if ($this->basePath !== null) {
            return $this->basePath;
        }

        $scriptName = $this->server['SCRIPT_NAME'] ?? '';

        if ($scriptName === '') {
            return $this->basePath = '';
        }

        $directory = str_replace('\\', '/', dirname($scriptName));

        if ($directory === '/' || $directory === '.') {
            return $this->basePath = '';
        }

        return $this->basePath = rtrim($directory, '/');
    }

    public function query(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    public function input(string $key = null, $default = null)
    {
        if ($key === null) {
            return array_merge($this->query, $this->body);
        }

        if (array_key_exists($key, $this->body)) {
            return $this->body[$key];
        }

        return $this->query[$key] ?? $default;
    }

    public function json(string $key = null, $default = null)
    {
        $contentType = $this->headers['Content-Type'] ?? '';

        if (stripos($contentType, 'application/json') === false) {
            return $key === null ? [] : $default;
        }

        static $decoded;

        if ($decoded === null) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true) ?: [];
        }

        if ($key === null) {
            return $decoded;
        }

        return $decoded[$key] ?? $default;
    }

    public function server(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->server;
        }

        return $this->server[$key] ?? $default;
    }

    public function header(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->headers;
        }

        return $this->headers[$key] ?? $default;
    }

    public function files(string $key = null)
    {
        if ($key === null) {
            return $this->files;
        }

        return $this->files[$key] ?? null;
    }

    public function cookies(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->cookies;
        }

        return $this->cookies[$key] ?? $default;
    }
}




