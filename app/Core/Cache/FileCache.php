<?php

namespace App\Core\Cache;

/**
 * Simple file-based caching system
 */
class FileCache
{
    protected string $cacheDir;
    protected int $defaultTtl;

    public function __construct(string $cacheDir, int $defaultTtl = 300)
    {
        $this->cacheDir = rtrim($cacheDir, '/\\');
        $this->defaultTtl = $defaultTtl;

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Get a value from cache
     */
    public function get(string $key, $default = null)
    {
        $file = $this->getCacheFile($key);

        if (!file_exists($file)) {
            return $default;
        }

        $data = @file_get_contents($file);
        if ($data === false) {
            return $default;
        }

        $cached = @json_decode($data, true);
        if (!is_array($cached) || !isset($cached['expires'], $cached['value'])) {
            return $default;
        }

        // Check if expired
        if ($cached['expires'] < time()) {
            @unlink($file);
            return $default;
        }

        return $cached['value'];
    }

    /**
     * Set a value in cache
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;
        $file = $this->getCacheFile($key);

        $data = [
            'expires' => time() + $ttl,
            'value' => $value,
            'created' => time()
        ];

        $json = json_encode($data);
        if ($json === false) {
            return false;
        }

        return @file_put_contents($file, $json, LOCK_EX) !== false;
    }

    /**
     * Delete a key from cache
     */
    public function delete(string $key): bool
    {
        $file = $this->getCacheFile($key);

        if (file_exists($file)) {
            return @unlink($file);
        }

        return true;
    }

    /**
     * Get the cache file path for a key
     */
    protected function getCacheFile(string $key): string
    {
        $hash = md5($key);
        return $this->cacheDir . '/' . $hash . '.cache';
    }
}
