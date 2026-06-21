<?php

declare(strict_types=1);

namespace Modules\Core\DTOs;

use Illuminate\Contracts\Auth\Authenticatable;
use InvalidArgumentException;

/** Authenticated identity only. Tenant and organization contexts are resolved independently. */
final readonly class CurrentUserContext
{
    /**
     * @param  array<string, mixed>  $tokenPayload
     */
    public function __construct(
        private Authenticatable $user,
        private int $userId,
        private string $guard,
        private ?string $provider,
        private ?string $applicationId,
        private array $tokenPayload = [],
    ) {
        if ($this->userId < 1) {
            throw new InvalidArgumentException('Current user identifier must be a positive integer.');
        }

        if (trim($this->guard) === '') {
            throw new InvalidArgumentException('Current user guard cannot be empty.');
        }

        $authIdentifier = $this->user->getAuthIdentifier();
        if (! is_numeric($authIdentifier) || (int) $authIdentifier !== $this->userId) {
            throw new InvalidArgumentException('Current user identifier does not match the authenticated user.');
        }
    }

    public function user(): Authenticatable
    {
        return $this->user;
    }

    public function userId(): int
    {
        return $this->userId;
    }

    public function guard(): string
    {
        return $this->guard;
    }

    public function provider(): ?string
    {
        return $this->provider;
    }

    public function applicationId(): ?string
    {
        return $this->applicationId;
    }

    /** @return array<string, mixed> */
    public function tokenPayload(): array
    {
        return $this->tokenPayload;
    }
}
