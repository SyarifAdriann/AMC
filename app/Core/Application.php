<?php

namespace App\Core;

use App\Core\Routing\Router;

class Application extends Container
{
    protected string $basePath;

    /**
     * @var array<string, mixed>
     */
    protected array $config = [];

    protected Router $router;

    public function __construct(string $basePath, array $config = [])
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        $this->config = $config;
        $this->router = new Router($this);

        $this->instance(self::class, $this);
    }

    /**
     * Application base path helper.
     */
    public function basePath(string $path = ''): string
    {
        if ($path === '') {
            return $this->basePath;
        }

        return $this->basePath . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
    }

    /**
     * Retrieve configuration value using dot notation.
     *
     * @param mixed $default
     * @return mixed
     */
    public function config(string $key, $default = null)
    {
        $segments = explode('.', $key);
        $value = $this->config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Merge additional configuration.
     */
    public function mergeConfig(array $config): void
    {
        $this->config = array_replace_recursive($this->config, $config);
    }

    /**
     * Access the router instance.
     */
    public function router(): Router
    {
        return $this->router;
    }
}

