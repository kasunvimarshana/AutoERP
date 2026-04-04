<?php
namespace Modules\Tenant\Domain\ValueObjects;

class DatabaseConfig
{
    public function __construct(
        public readonly string $connection = 'mysql',
        public readonly ?string $database = null,
        public readonly ?string $host = null,
        public readonly ?int $port = null,
        public readonly ?string $username = null,
        public readonly ?string $password = null,
    ) {}

    /** Password is intentionally excluded for security (never serialized). */
    public function toArray(): array
    {
        return [
            'connection' => $this->connection,
            'database'   => $this->database,
            'host'       => $this->host,
            'port'       => $this->port,
            'username'   => $this->username,
        ];
    }
}
