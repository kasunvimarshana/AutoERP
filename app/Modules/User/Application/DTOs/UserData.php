<?php

declare(strict_types=1);

namespace Modules\User\Application\DTOs;

final readonly class UserData
{
    /**
     * @param  array<string, mixed>|null  $preferences
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public string $firstName,
        public ?string $lastName,
        public string $email,
        public ?string $emailVerifiedAt,
        public ?string $password = null,
        public string $status = 'active',
        public ?string $avatarPath = null,
        public ?string $phone = null,
        public ?array $preferences = null,
        public ?string $dateOfBirth = null,
        public ?string $gender = null,
        public ?string $maritalStatus = null,
        public ?int $tenantId = null,
        public ?int $organizationUnitId = null,
        public ?array $metadata = null,
    )
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            firstName: (string) $data['first_name'],
            lastName: $data['last_name'] ?? null,
            email: (string) $data['email'],
            emailVerifiedAt: $data['email_verified_at'] ?? null,
            password: isset($data['password']) ? (string) $data['password'] : null,
            status: (string) ($data['status'] ?? 'active'),
            avatarPath: $data['avatar_path'] ?? null,
            phone: $data['phone'] ?? null,
            preferences: $data['preferences'] ?? null,
            dateOfBirth: $data['date_of_birth'] ?? null,
            gender: $data['gender'] ?? null,
            maritalStatus: $data['marital_status'] ?? null,
            tenantId: isset($data['tenant_id']) ? (int) $data['tenant_id'] : null,
            organizationUnitId: isset($data['organization_unit_id']) ? (int) $data['organization_unit_id'] : null,
            metadata: $data['metadata'] ?? null,
        );
    }
}
