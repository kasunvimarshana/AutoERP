<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services;

use Illuminate\Http\Request;
use Modules\Core\Contracts\CurrentOrganizationUnitContextResolverInterface;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\OrganizationUnitUserAccessCheckerInterface;
use Modules\Core\DTOs\CurrentOrganizationUnitContext;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Exceptions\CurrentOrganizationUnitContextResolutionException;
use Modules\OrganizationUnit\Repositories\OrganizationUnitRepositoryInterface;

final class CurrentOrganizationUnitContextResolver implements CurrentOrganizationUnitContextResolverInterface
{
    public function __construct(
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly OrganizationUnitRepositoryInterface $organizationUnits,
        private readonly OrganizationUnitUserAccessCheckerInterface $userAccess,
    ) {}

    public function resolve(Request $request): ?CurrentOrganizationUnitContext
    {
        $tenantId = $this->currentTenant->currentTenantId();
        $user = $this->currentUser->current();
        if ($tenantId === null || $user === null) {
            return null;
        }

        $tokenOrganizationUnitId = $this->positiveInt($user->tokenPayload()['organization_unit_id'] ?? null);
        if ($tokenOrganizationUnitId !== null) {
            return $this->resolveById($tokenOrganizationUnitId, $tenantId, $user->applicationId(), 'authenticated_session');
        }

        $defaults = $this->userAccess->defaultOrganizationUnitIds($user->userId(), $tenantId);
        if (count($defaults) > 1) {
            throw new CurrentOrganizationUnitContextResolutionException(
                'Multiple default organization-unit assignments exist for the active tenant and user.',
            );
        }

        $defaultId = $defaults[0] ?? null;
        return $defaultId === null
            ? null
            : $this->resolveById($defaultId, $tenantId, $user->applicationId(), 'default_membership');
    }

    public function hasAccess(Request $request, CurrentOrganizationUnitContext $context): bool
    {
        $user = $this->currentUser->current();
        $tenantId = $this->currentTenant->currentTenantId();
        return $user !== null
            && $tenantId === $context->tenantId()
            && $context->isActive()
            && $this->userAccess->canAccessOrganizationUnit(
                $user->userId(),
                $context->tenantId(),
                $context->organizationUnitId(),
            );
    }

    private function resolveById(int $organizationUnitId, int $tenantId, ?string $applicationId, string $source): CurrentOrganizationUnitContext
    {
        $organizationUnit = $this->organizationUnits->findById($organizationUnitId);
        if ($organizationUnit === null || (int) $organizationUnit->get('tenant_id') !== $tenantId) {
            throw new CurrentOrganizationUnitContextResolutionException(
                'The authenticated organization-unit scope could not be resolved in the active tenant.',
            );
        }
        if (! (bool) $organizationUnit->get('is_active') || $organizationUnit->get('retired_at') !== null) {
            throw new CurrentOrganizationUnitContextResolutionException(
                'The authenticated organization-unit scope is inactive or retired.',
            );
        }

        return $this->toContext($organizationUnit, $tenantId, $applicationId, $source);
    }

    private function toContext(DataRecord $record, int $tenantId, ?string $applicationId, string $source): CurrentOrganizationUnitContext
    {
        return new CurrentOrganizationUnitContext(
            $record,
            (int) $record->id(),
            $tenantId,
            $this->nullableString($record->get('code')),
            $this->nullableString($record->get('path')),
            (string) $record->require('name'),
            (bool) $record->get('is_active'),
            $applicationId,
            $source,
        );
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }
        $normalized = trim((string) $value);
        return $normalized === '' ? null : $normalized;
    }
}
