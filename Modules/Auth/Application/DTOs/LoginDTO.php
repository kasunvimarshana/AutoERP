<?php

declare(strict_types=1);

namespace Modules\Auth\Application\DTOs;

use Modules\Core\Application\DTOs\DataTransferObject;

/**
 * Login DTO.
 *
 * Carries validated login credentials from the controller to the service.
 */
final class LoginDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly ?string $deviceName = null,
    ) {}

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data): static
    {
        return new static(
            email: $data['email'],
            password: $data['password'],
            deviceName: $data['device_name'] ?? null,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
            'device_name' => $this->deviceName,
        ];
    }
}
