<?php

namespace Modules\Core\Domain\ValueObjects;

class DatabaseConfig extends ValueObject
{
    private string $driver;
    private string $host;
    private int $port;
    private string $database;
    private string $username;
    private string $password;

    public function __construct(
        string $driver,
        string $host,
        int $port,
        string $database,
        string $username,
        string $password
    ) {
        $this->driver = $driver;
        $this->host = $host;
        $this->port = $port;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
    }

    // Getters...
    public function getDriver(): string { return $this->driver; }
    public function getHost(): string { return $this->host; }
    public function getPort(): int { return $this->port; }
    public function getDatabase(): string { return $this->database; }
    public function getUsername(): string { return $this->username; }
    public function getPassword(): string { return $this->password; }

    public function toArray(): array
    {
        return [
            'driver'   => $this->driver,
            'host'     => $this->host,
            'port'     => $this->port,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->password,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new static(
            $data['driver'] ?? 'mysql',
            $data['host'] ?? '127.0.0.1',
            (int)($data['port'] ?? 3306),
            $data['database'] ?? '',
            $data['username'] ?? '',
            $data['password'] ?? ''
        );
    }
}
