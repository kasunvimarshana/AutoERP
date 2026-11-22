<?php

declare(strict_types=1);

namespace Modules\Configuration\Data;

final readonly class MailConfiguration
{
    public function __construct(
        public string $mailer,
        public string $host,
        public int $port,
        public ?string $username,
        public ?string $password,
        public ?string $encryption,
        public string $fromAddress,
        public string $fromName,
    ) {}

    /**
     * Build Laravel mailer configuration array for dynamic mailer connections.
     *
     * @return array<string, mixed>
     */
    public function toMailerConfig(): array
    {
        return [
            'transport' => $this->mailer,
            'host' => $this->host,
            'port' => $this->port,
            'encryption' => $this->encryption === 'none' ? null : $this->encryption,
            'username' => $this->username,
            'password' => $this->password,
            'timeout' => null,
        ];
    }
}
