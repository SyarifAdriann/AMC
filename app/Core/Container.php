<?php

namespace App\Core;

class Container
{
    /**
     * @var array<string, callable|mixed>
     */
    protected array $bindings = [];

    /**
     * @var array<string, mixed>
     */
    protected array $instances = [];

    /**
     * Register a binding.
     */
    public function bind(string $abstract, callable $concrete, bool $shared = false): void
    {
        $this->bindings[$abstract] = ['factory' => $concrete, 'shared' => $shared];
    }

    /**
     * Register a singleton binding.
     */
    public function singleton(string $abstract, callable $concrete): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Determine if the container has a binding.
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Resolve a binding.
     */
    public function make(string $abstract)
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (!isset($this->bindings[$abstract])) {
            throw new \RuntimeException("No binding registered for {$abstract}");
        }

        $binding = $this->bindings[$abstract];
        $object = ($binding['factory'])($this);

        if ($binding['shared']) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Manually set an instance.
     */
    public function instance(string $abstract, $instance): void
    {
        $this->instances[$abstract] = $instance;
    }
}

