<?php

declare(strict_types=1);

namespace Modules\Auth\Application\DTOs;

use Modules\Core\Application\DTOs\DataTransferObject;

/**
 * Register DTO.
 *
 * Carries validated new-user registration data from the controller to the service.
 */
final class RegisterDTO extends DataTransferObject
{
    public function __construct(
        public readonly int    $tenantId,
        public readonly string $name,
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
            tenantId:   (int) $data['tenant_id'],
            name:       $data['name'],
            email:      $data['email'],
            password:   $data['password'],
            deviceName: $data['device_name'] ?? null,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return [
            'tenant_id'   => $this->tenantId,
            'name'        => $this->name,
            'email'       => $this->email,
            'password'    => $this->password,
            'device_name' => $this->deviceName,
        ];
    }
}
