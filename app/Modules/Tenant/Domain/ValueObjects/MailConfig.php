<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\ValueObjects;

use Modules\Core\Domain\ValueObjects\ValueObject;

class MailConfig extends ValueObject
{
    private string $driver;

    private string $host;

    private int $port;

    private string $username;

    private string $password;

    private ?string $encryption;

    private ?string $fromAddress;

    private ?string $fromName;

    public function __construct(
        string $driver,
        string $host,
        int $port,
        string $username,
        string $password,
        ?string $encryption = null,
        ?string $fromAddress = null,
        ?string $fromName = null
    ) {
        $this->driver = $driver;
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->encryption = $encryption;
        $this->fromAddress = $fromAddress;
        $this->fromName = $fromName;
    }

    public function toArray(): array
    {
        return [
            'driver' => $this->driver,
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
            'password' => $this->password,
            'encryption' => $this->encryption,
            'from' => [
                'address' => $this->fromAddress,
                'name' => $this->fromName,
            ],
        ];
    }

    public static function fromArray(array $data): static
    {
        return new static(
            $data['driver'] ?? 'smtp',
            $data['host'] ?? 'localhost',
            (int) ($data['port'] ?? 587),
            $data['username'] ?? '',
            $data['password'] ?? '',
            $data['encryption'] ?? null,
            $data['from']['address'] ?? null,
            $data['from']['name'] ?? null
        );
    }
}
