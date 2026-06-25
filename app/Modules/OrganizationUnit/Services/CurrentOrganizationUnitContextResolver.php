<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services;

use Illuminate\Contracts\Auth\Authenticatable;
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
        $tenantId = $this->resolveTenantId();
        if ($tenantId === null) {
            return null;
        }

        $applicationId = $this->resolveApplicationId();

        $explicit = $this->resolveExplicitOrganizationUnit($request, $tenantId, $applicationId);
        if ($explicit !== null) {
            return $explicit;
        }

        $userId = $this->resolveAuthenticatedUserId($request);
        if ($userId === null) {
            return null;
        }

        $defaultOrganizationUnitIds = $this->userAccess->defaultOrganizationUnitIds($userId, $tenantId);
        if (count($defaultOrganizationUnitIds) > 1) {
            throw new CurrentOrganizationUnitContextResolutionException(
                'Multiple default organization unit assignments exist for the active tenant and user.',
            );
        }

        $organizationUnitId = $defaultOrganizationUnitIds[0] ?? null;
        if ($organizationUnitId === null) {
            return null;
        }

        $organizationUnit = $this->organizationUnits->findById($organizationUnitId);
        if ($organizationUnit === null) {
            throw new CurrentOrganizationUnitContextResolutionException(
                'Default organization unit membership could not be resolved.',
            );
        }

        return $this->toContext($organizationUnit, $tenantId, $applicationId, 'default_membership');
    }

    public function hasAccess(Request $request, CurrentOrganizationUnitContext $context): bool
    {
        if (! $context->isActive() || $context->organizationUnitId() <= 0 || $context->tenantId() <= 0) {
            return false;
        }

        $tenantId = $this->resolveTenantId();
        if ($tenantId === null || $tenantId !== $context->tenantId()) {
            return false;
        }

        $userId = $this->resolveAuthenticatedUserId($request);
        if ($userId === null) {
            return false;
        }

        return $this->userAccess->canAccessOrganizationUnit(
            $userId,
            $context->tenantId(),
            $context->organizationUnitId(),
        );
    }

    private function resolveExplicitOrganizationUnit(
        Request $request,
        int $tenantId,
        ?string $applicationId,
    ): ?CurrentOrganizationUnitContext {
        $contexts = [];

        foreach ($this->configArray('id_input_keys', ['organization_unit_id']) as $key) {
            $contexts[] = $this->contextFromIdSignal($request->input($key), $tenantId, $applicationId);
        }

        foreach ($this->configArray('id_route_keys', ['organization_unit_id', 'organization_unit']) as $key) {
            $contexts[] = $this->contextFromIdSignal($request->route($key), $tenantId, $applicationId);
        }

        foreach ($this->configArray('id_header_keys', ['X-Organization-Unit-Id', 'X-Organization-Unit']) as $key) {
            $contexts[] = $this->contextFromIdSignal($request->headers->get($key), $tenantId, $applicationId);
        }

        foreach ($this->configArray('code_input_keys', ['organization_unit_code']) as $key) {
            $contexts[] = $this->contextFromCodeSignal($this->stringSignal($request->input($key)), $tenantId, $applicationId);
        }

        foreach ($this->configArray('code_route_keys', ['organization_unit_code']) as $key) {
            $contexts[] = $this->contextFromCodeSignal($this->stringSignal($request->route($key)), $tenantId, $applicationId);
        }

        foreach ($this->configArray('code_header_keys', ['X-Organization-Unit-Code']) as $key) {
            $contexts[] = $this->contextFromCodeSignal($this->stringSignal($request->headers->get($key)), $tenantId, $applicationId);
        }

        foreach ($this->configArray('path_input_keys', ['organization_unit_path']) as $key) {
            $contexts[] = $this->contextFromPathSignal($this->stringSignal($request->input($key)), $tenantId, $applicationId);
        }

        foreach ($this->configArray('path_header_keys', ['X-Organization-Unit-Path']) as $key) {
            $contexts[] = $this->contextFromPathSignal($this->stringSignal($request->headers->get($key)), $tenantId, $applicationId);
        }

        foreach ($this->configArray('name_input_keys', ['organization_unit_name']) as $key) {
            $contexts[] = $this->contextFromNameSignal($this->stringSignal($request->input($key)), $tenantId, $applicationId);
        }

        foreach ($this->configArray('name_header_keys', ['X-Organization-Unit-Name']) as $key) {
            $contexts[] = $this->contextFromNameSignal($this->stringSignal($request->headers->get($key)), $tenantId, $applicationId);
        }

        $contexts = array_values(array_filter(
            $contexts,
            static fn ($context): bool => $context instanceof CurrentOrganizationUnitContext,
        ));

        if ($contexts === []) {
            return null;
        }

        $uniqueIds = array_values(array_unique(array_map(
            static fn (CurrentOrganizationUnitContext $context): int => $context->organizationUnitId(),
            $contexts,
        )));

        if (count($uniqueIds) > 1) {
            throw new CurrentOrganizationUnitContextResolutionException(
                'Requested organization unit metadata resolved to multiple organization units.',
            );
        }

        return $contexts[0];
    }

    private function contextFromIdSignal(mixed $value, int $tenantId, ?string $applicationId): ?CurrentOrganizationUnitContext
    {
        if ($value === null || $value === '') {
            return null;
        }

        $organizationUnitId = $this->toNullableInt($value);
        if ($organizationUnitId === null) {
            throw new CurrentOrganizationUnitContextResolutionException('Requested organization unit identifier is invalid.');
        }

        $organizationUnit = $this->organizationUnits->findById($organizationUnitId);
        if ($organizationUnit === null) {
            throw new CurrentOrganizationUnitContextResolutionException('Requested organization unit could not be resolved.');
        }

        return $this->toContext($organizationUnit, $tenantId, $applicationId, 'request_metadata');
    }

    private function contextFromCodeSignal(?string $value, int $tenantId, ?string $applicationId): ?CurrentOrganizationUnitContext
    {
        if ($value === null) {
            return null;
        }

        $organizationUnit = $this->organizationUnits->findByTenantAndCode($tenantId, $value);
        if ($organizationUnit === null) {
            throw new CurrentOrganizationUnitContextResolutionException('Requested organization unit could not be resolved.');
        }

        return $this->toContext($organizationUnit, $tenantId, $applicationId, 'request_metadata');
    }

    private function contextFromPathSignal(?string $value, int $tenantId, ?string $applicationId): ?CurrentOrganizationUnitContext
    {
        if ($value === null) {
            return null;
        }

        $organizationUnit = $this->organizationUnits->findByTenantAndPath($tenantId, $value);
        if ($organizationUnit === null) {
            throw new CurrentOrganizationUnitContextResolutionException('Requested organization unit could not be resolved.');
        }

        return $this->toContext($organizationUnit, $tenantId, $applicationId, 'request_metadata');
    }

    private function contextFromNameSignal(?string $value, int $tenantId, ?string $applicationId): ?CurrentOrganizationUnitContext
    {
        if ($value === null) {
            return null;
        }

        $organizationUnit = $this->organizationUnits->findByTenantAndName($tenantId, $value);
        if ($organizationUnit === null) {
            throw new CurrentOrganizationUnitContextResolutionException('Requested organization unit could not be resolved.');
        }

        return $this->toContext($organizationUnit, $tenantId, $applicationId, 'request_metadata');
    }

    private function resolveTenantId(): ?int
    {
        $tenantId = $this->currentTenant->currentTenantId();

        return $tenantId !== null && $tenantId > 0 ? $tenantId : null;
    }

    private function resolveAuthenticatedUserId(Request $request): ?int
    {
        $userId = $this->currentUser->currentUserId();
        if ($userId !== null && $userId > 0) {
            return $userId;
        }

        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            return null;
        }

        return $this->toNullableInt($user->getAuthIdentifier());
    }

    private function resolveApplicationId(): ?string
    {
        return $this->currentTenant->currentApplicationId()
            ?? $this->currentUser->currentApplicationId();
    }

    private function toContext(
        DataRecord $organizationUnit,
        int $tenantId,
        ?string $applicationId,
        string $source,
    ): CurrentOrganizationUnitContext {
        $organizationUnitId = $this->toNullableInt($organizationUnit->get('id'));
        $organizationUnitTenantId = $this->toNullableInt($organizationUnit->get('tenant_id'));
        $name = $this->stringSignal($organizationUnit->get('name'));

        if ($organizationUnitId === null || $organizationUnitTenantId === null || $name === null) {
            throw new CurrentOrganizationUnitContextResolutionException('Resolved organization unit record is incomplete.');
        }

        if ($organizationUnitTenantId !== $tenantId) {
            throw new CurrentOrganizationUnitContextResolutionException(
                'Resolved organization unit does not belong to the active tenant.',
            );
        }

        if (! $this->toBool($organizationUnit->get('is_active'))) {
            throw new CurrentOrganizationUnitContextResolutionException(
                'Resolved organization unit is not active.',
            );
        }

        return new CurrentOrganizationUnitContext(
            $organizationUnit,
            $organizationUnitId,
            $tenantId,
            $this->stringSignal($organizationUnit->get('code')),
            $this->stringSignal($organizationUnit->get('path')),
            $name,
            $this->toBool($organizationUnit->get('is_active')),
            $applicationId,
            $source,
        );
    }

    /**
     * @param  list<string>  $fallback
     * @return list<string>
     */
    private function configArray(string $key, array $fallback): array
    {
        $resolved = config('organization-unit.resolution.signals.'.$key, $fallback);
        if (! is_array($resolved)) {
            return $fallback;
        }

        $values = [];
        foreach ($resolved as $value) {
            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $values[] = trim($value);
        }

        /** @var list<string> $values */
        return array_values(array_unique($values));
    }

    private function stringSignal(mixed $value): ?string
    {
        if ($value === null || ! is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return false;
    }
}
