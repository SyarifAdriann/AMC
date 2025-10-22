<?php

namespace App\Core\Database;

use App\Core\Application;
use PDO;
use RuntimeException;

class DatabaseManager
{
    protected Application $app;

    /**
     * @var array<string, PDO>
     */
    protected array $connections = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function connection(?string $name = null): PDO
    {
        $name = $name ?: (string) $this->app->config('database.default', 'mysql');

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->createConnection($name);
        }

        return $this->connections[$name];
    }

    protected function createConnection(string $name): PDO
    {
        $config = $this->app->config("database.connections.{$name}");

        if (!$config) {
            throw new RuntimeException("Database connection '{$name}' is not configured.");
        }

        $driver = $config['driver'] ?? 'mysql';

        switch ($driver) {
            case 'mysql':
                return $this->createMysqlConnection($config);
            default:
                throw new RuntimeException("Unsupported database driver '{$driver}'.");
        }
    }

    protected function createMysqlConnection(array $config): PDO
    {
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? '3306';
        $database = $config['database'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';
        $username = $config['username'] ?? 'root';
        $password = $config['password'] ?? '';
        $options = $config['options'] ?? [];

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $database, $charset);

        return new PDO($dsn, $username, $password, $options);
    }
}
