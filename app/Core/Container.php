<?php

namespace App\Core;

class Container
{
    /**
     * @var array<string, callable>
     */
    protected array $bindings = [];

    /**
     * @var array<string, mixed>
     */
    protected array $instances = [];

    /**
     * Register a singleton binding. Every binding is shared: the factory runs
     * once and make() returns that same instance thereafter.
     */
    public function singleton(string $abstract, callable $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
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

        return $this->instances[$abstract] = ($this->bindings[$abstract])($this);
    }

    /**
     * Manually set an instance.
     */
    public function instance(string $abstract, $instance): void
    {
        $this->instances[$abstract] = $instance;
    }
}
