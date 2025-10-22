<?php

namespace App\Core\View;

use InvalidArgumentException;

class View
{
    protected string $basePath;

    /**
     * @var array<string, mixed>
     */
    protected static array $shared = [];

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
    }

    public static function share(string $key, $value): void
    {
        self::$shared[$key] = $value;
    }

    public function render(string $template, array $data = []): string
    {
        $path = $this->resolvePath($template);

        if (!file_exists($path)) {
            throw new InvalidArgumentException("View '{$template}' not found at {$path}");
        }

        $data = array_merge(self::$shared, $data);
        extract($data, EXTR_SKIP);

        ob_start();
        include $path;
        return ob_get_clean() ?: '';
    }

    protected function resolvePath(string $template): string
    {
        $template = str_replace(['.', '::'], DIRECTORY_SEPARATOR, $template);
        return $this->basePath . DIRECTORY_SEPARATOR . $template . '.php';
    }
}

