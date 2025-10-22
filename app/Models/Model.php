<?php

namespace App\Models;

abstract class Model
{
    /**
     * Raw attribute storage for the model.
     *
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public static function fromArray(array $attributes): static
    {
        return new static($attributes);
    }

    public function fill(array $attributes): void
    {
        $this->attributes = $attributes + $this->attributes;
    }

    public function get(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }

    public function toArray(): array
    {
        return $this->attributes;
    }
}
