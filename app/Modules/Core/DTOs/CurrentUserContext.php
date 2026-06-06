<?php

declare(strict_types=1);

namespace Modules\Core\DTOs;

use Illuminate\Contracts\Auth\Authenticatable;

final readonly class CurrentUserContext
{
    /**
     * @param  array<string, mixed>  $tokenPayload
     */
    public function __construct(
        private Authenticatable $user,
        private string $userId,
        private string $guard,
        private ?string $provider,
        private ?int $tenantId,
        private ?int $organizationUnitId,
        private ?string $applicationId,
        private array $tokenPayload = [],
    ) {}

    public function user(): Authenticatable
    {
        return $this->user;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function userIdAsInt(): int
    {
        return (int) $this->userId;
    }

    public function guard(): string
    {
        return $this->guard;
    }

    public function provider(): ?string
    {
        return $this->provider;
    }

    public function tenantId(): ?int
    {
        return $this->tenantId;
    }

    public function organizationUnitId(): ?int
    {
        return $this->organizationUnitId;
    }

    public function applicationId(): ?string
    {
        return $this->applicationId;
    }

    /**
     * @return array<string, mixed>
     */
    public function tokenPayload(): array
    {
        return $this->tokenPayload;
    }

    /**
     * @return array<string, int|string|null>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'guard' => $this->guard,
            'provider' => $this->provider,
            'tenant_id' => $this->tenantId,
            'organization_unit_id' => $this->organizationUnitId,
            'application_id' => $this->applicationId,
        ];
    }
}
