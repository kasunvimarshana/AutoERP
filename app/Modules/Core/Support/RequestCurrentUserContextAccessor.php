<?php

declare(strict_types=1);

namespace Modules\Core\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use LogicException;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\DTOs\CurrentUserContext;

final class RequestCurrentUserContextAccessor implements CurrentUserContextAccessorInterface
{
    public function __construct(
        private readonly Request $request,
        private readonly string $requestAttribute,
        private readonly string $guardAttribute,
        private readonly string $providerAttribute,
        private readonly string $tenantAttribute,
        private readonly string $organizationUnitAttribute,
        private readonly string $applicationAttribute,
        private readonly string $tokenPayloadAttribute,
    ) {}

    public function current(): ?CurrentUserContext
    {
        $user = $this->request->user();
        if (! $user instanceof Authenticatable) {
            return null;
        }

        $serialized = $this->request->attributes->get($this->requestAttribute);
        $serialized = is_array($serialized) ? $serialized : [];

        $userId = $this->stringOrNull($serialized['user_id'] ?? null)
            ?? $this->stringOrNull($user->getAuthIdentifier());
        if ($userId === null) {
            return null;
        }

        $tokenPayload = $this->request->attributes->get($this->tokenPayloadAttribute);
        $tokenPayload = is_array($tokenPayload) ? $tokenPayload : [];

        return new CurrentUserContext(
            $user,
            $userId,
            $this->stringOrFallback(
                $serialized['guard'] ?? $this->request->attributes->get($this->guardAttribute),
                'web',
            ),
            $this->stringOrNull(
                $serialized['provider'] ?? $this->request->attributes->get($this->providerAttribute),
            ),
            $this->intOrNull($serialized['tenant_id'] ?? $this->request->attributes->get($this->tenantAttribute)),
            $this->intOrNull(
                $serialized['organization_unit_id']
                    ?? $this->request->attributes->get($this->organizationUnitAttribute),
            ),
            $this->stringOrNull(
                $serialized['application_id'] ?? $this->request->attributes->get($this->applicationAttribute),
            ),
            $tokenPayload,
        );
    }

    public function requireCurrent(): CurrentUserContext
    {
        $context = $this->current();
        if ($context === null) {
            throw new LogicException('Current user context is not available on the active request.');
        }

        return $context;
    }

    public function currentUserId(): ?int
    {
        return $this->current()?->userIdAsInt();
    }

    public function currentTenantId(): ?int
    {
        return $this->current()?->tenantId();
    }

    public function currentOrganizationUnitId(): ?int
    {
        return $this->current()?->organizationUnitId();
    }

    public function currentApplicationId(): ?string
    {
        return $this->current()?->applicationId();
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function stringOrFallback(mixed $value, string $fallback): string
    {
        return $this->stringOrNull($value) ?? $fallback;
    }

    private function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }
}
