<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextResolverInterface;
use Modules\Core\Application\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Application\DTO\CurrentTenantContext;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Exceptions\CurrentTenantContextResolutionException;
use Modules\Tenant\Application\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Modules\User\Application\Repositories\UserTenantRepositoryInterface;
use Throwable;

final class CurrentTenantContextResolver implements CurrentTenantContextResolverInterface
{
    public function __construct(
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantDomainRepositoryInterface $tenantDomains,
        private readonly UserTenantRepositoryInterface $userTenants,
    ) {
    }

    public function resolve(Request $request): ?CurrentTenantContext
    {
        $applicationId = $this->resolveApplicationId($request);

        $explicit = $this->resolveExplicitTenant($request, $applicationId);
        if ($explicit !== null) {
            return $explicit;
        }

        $hostTenant = $this->resolveHostTenant($request, $applicationId);
        if ($hostTenant !== null) {
            return $hostTenant;
        }

        $authenticatedTenantId = $this->resolveAuthenticatedTenantId($request);
        if ($authenticatedTenantId === null) {
            return null;
        }

        $tenant = $this->tenants->findById($authenticatedTenantId);
        if ($tenant === null) {
            return null;
        }

        return $this->toContext($tenant, $applicationId, 'authenticated_user');
    }

    public function hasAccess(Request $request, CurrentTenantContext $context): bool
    {
        $resolvedTenantId = $context->tenantId();
        if ($resolvedTenantId <= 0) {
            return false;
        }

        $authenticatedTenantId = $this->resolveAuthenticatedTenantId($request);
        if (
            $this->enforceAuthenticatedTenantMatch()
            && $authenticatedTenantId !== null
            && $authenticatedTenantId !== $resolvedTenantId
        ) {
            return false;
        }

        if ($authenticatedTenantId !== null && $authenticatedTenantId === $resolvedTenantId) {
            return true;
        }

        $userId = $this->resolveAuthenticatedUserId($request);
        if ($userId === null) {
            return true;
        }

        return $this->userTenants->existsForTenantAndUser($resolvedTenantId, $userId);
    }

    private function resolveExplicitTenant(Request $request, ?string $applicationId): ?CurrentTenantContext
    {
        $contexts = [];

        foreach ($this->configArray('id_input_keys', ['tenant_id']) as $key) {
            $contexts[] = $this->contextFromTenantIdSignal($request->input($key), $applicationId, 'request_metadata');
        }

        foreach ($this->configArray('id_route_keys', ['tenant_id']) as $key) {
            $contexts[] = $this->contextFromTenantIdSignal($request->route($key), $applicationId, 'request_metadata');
        }

        foreach ($this->configArray('id_header_keys', ['X-Tenant-Id']) as $key) {
            $contexts[] = $this->contextFromTenantIdSignal(
                $request->headers->get($key),
                $applicationId,
                'request_metadata',
            );
        }

        foreach ($this->configArray('code_input_keys', ['tenant_code']) as $key) {
            $contexts[] = $this->contextFromRecordSignal(
                $this->stringSignal($request->input($key)),
                'code',
                $applicationId,
            );
        }

        foreach ($this->configArray('code_route_keys', ['tenant', 'tenant_code']) as $key) {
            $contexts[] = $this->contextFromRecordSignal(
                $this->stringSignal($request->route($key)),
                'code',
                $applicationId,
            );
        }

        foreach ($this->configArray('code_header_keys', ['X-Tenant-Code']) as $key) {
            $contexts[] = $this->contextFromRecordSignal(
                $this->stringSignal($request->headers->get($key)),
                'code',
                $applicationId,
            );
        }

        foreach ($this->configArray('uuid_input_keys', ['tenant_uuid']) as $key) {
            $contexts[] = $this->contextFromRecordSignal(
                $this->stringSignal($request->input($key)),
                'uuid',
                $applicationId,
            );
        }

        foreach ($this->configArray('uuid_route_keys', ['tenant_uuid']) as $key) {
            $contexts[] = $this->contextFromRecordSignal(
                $this->stringSignal($request->route($key)),
                'uuid',
                $applicationId,
            );
        }

        foreach ($this->configArray('uuid_header_keys', ['X-Tenant-Uuid']) as $key) {
            $contexts[] = $this->contextFromRecordSignal(
                $this->stringSignal($request->headers->get($key)),
                'uuid',
                $applicationId,
            );
        }

        foreach ($this->configArray('isolation_key_input_keys', ['tenant_isolation_key']) as $key) {
            $contexts[] = $this->contextFromRecordSignal(
                $this->stringSignal($request->input($key)),
                'isolation',
                $applicationId,
            );
        }

        foreach ($this->configArray('isolation_key_route_keys', ['tenant_isolation_key']) as $key) {
            $contexts[] = $this->contextFromRecordSignal(
                $this->stringSignal($request->route($key)),
                'isolation',
                $applicationId,
            );
        }

        foreach ($this->configArray('isolation_key_header_keys', ['X-Tenant-Isolation-Key']) as $key) {
            $contexts[] = $this->contextFromRecordSignal(
                $this->stringSignal($request->headers->get($key)),
                'isolation',
                $applicationId,
            );
        }

        foreach ($this->configArray('domain_input_keys', ['tenant_domain']) as $key) {
            $contexts[] = $this->contextFromDomainSignal($this->stringSignal($request->input($key)), $applicationId);
        }

        foreach ($this->configArray('domain_header_keys', ['X-Tenant-Domain']) as $key) {
            $contexts[] = $this->contextFromDomainSignal(
                $this->stringSignal($request->headers->get($key)),
                $applicationId,
            );
        }

        $contexts = array_values(array_filter(
            $contexts,
            static fn ($context): bool => $context instanceof CurrentTenantContext,
        ));

        if ($contexts === []) {
            return null;
        }

        $uniqueTenantIds = array_values(array_unique(array_map(
            static fn (CurrentTenantContext $context): int => $context->tenantId(),
            $contexts,
        )));

        if (count($uniqueTenantIds) > 1) {
            throw new CurrentTenantContextResolutionException(
                'Requested tenant metadata resolved to multiple tenants.',
            );
        }

        return $contexts[0];
    }

    private function resolveHostTenant(Request $request, ?string $applicationId): ?CurrentTenantContext
    {
        if ($this->hasExplicitContextSignals($request)) {
            return null;
        }

        $host = $this->normalizeDomain($request->getHost());
        if ($host === null) {
            return null;
        }

        $domain = $this->tenantDomains->findByDomain($host);
        if ($domain === null) {
            return null;
        }

        $tenantId = $this->toNullableInt($domain->get('tenant_id'));
        if ($tenantId === null) {
            return null;
        }

        $tenant = $this->tenants->findById($tenantId);
        if ($tenant === null) {
            return null;
        }

        return $this->toContext($tenant, $applicationId, 'request_host', $host);
    }

    private function contextFromTenantIdSignal(
        mixed $value,
        ?string $applicationId,
        string $source,
    ): ?CurrentTenantContext {
        if ($value === null || $value === '') {
            return null;
        }

        $tenantId = $this->toNullableInt($value);
        if ($tenantId === null) {
            throw new CurrentTenantContextResolutionException('Requested tenant identifier is invalid.');
        }

        $tenant = $this->tenants->findById($tenantId);
        if ($tenant === null) {
            throw new CurrentTenantContextResolutionException('Requested tenant could not be resolved.');
        }

        return $this->toContext($tenant, $applicationId, $source);
    }

    private function contextFromRecordSignal(
        ?string $value,
        string $type,
        ?string $applicationId,
    ): ?CurrentTenantContext {
        if ($value === null) {
            return null;
        }

        $tenant = match ($type) {
            'code' => $this->tenants->findByCode($value),
            'uuid' => $this->tenants->findByUuid($value),
            'isolation' => $this->tenants->findByIsolationKey($value),
            default => null,
        };

        if ($tenant === null && $type === 'code') {
            $tenantId = $this->toNullableInt($value);
            if ($tenantId !== null) {
                $tenant = $this->tenants->findById($tenantId);
            }
        }

        if ($tenant === null) {
            throw new CurrentTenantContextResolutionException('Requested tenant could not be resolved.');
        }

        return $this->toContext($tenant, $applicationId, 'request_metadata');
    }

    private function contextFromDomainSignal(?string $value, ?string $applicationId): ?CurrentTenantContext
    {
        if ($value === null) {
            return null;
        }

        $normalizedDomain = $this->normalizeDomain($value);
        if ($normalizedDomain === null) {
            throw new CurrentTenantContextResolutionException('Requested tenant domain is invalid.');
        }

        $domain = $this->tenantDomains->findByDomain($normalizedDomain);
        if ($domain === null) {
            throw new CurrentTenantContextResolutionException('Requested tenant could not be resolved.');
        }

        $tenantId = $this->toNullableInt($domain->get('tenant_id'));
        if ($tenantId === null) {
            throw new CurrentTenantContextResolutionException('Requested tenant could not be resolved.');
        }

        $tenant = $this->tenants->findById($tenantId);
        if ($tenant === null) {
            throw new CurrentTenantContextResolutionException('Requested tenant could not be resolved.');
        }

        return $this->toContext($tenant, $applicationId, 'request_metadata', $normalizedDomain);
    }

    private function resolveAuthenticatedTenantId(Request $request): ?int
    {
        $tenantId = $this->currentUser->currentTenantId();
        if ($tenantId !== null && $tenantId > 0) {
            return $tenantId;
        }

        $user = $request->user();

        return $this->toNullableInt(data_get($user, 'tenant_id'));
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

    private function resolveApplicationId(Request $request): ?string
    {
        foreach ($this->configArray('application_input_keys', ['application_id', 'app_id', 'client_id']) as $key) {
            $value = $request->input($key);
            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        $applicationHeaderKeys = $this->configArray(
            'application_header_keys',
            ['X-Application-Id', 'X-App-Id', 'X-Client-Id'],
        );

        foreach ($applicationHeaderKeys as $key) {
            $value = $request->headers->get($key);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return $this->currentUser->currentApplicationId() ?? $this->currentTenant->currentApplicationId();
    }

    private function hasExplicitContextSignals(Request $request): bool
    {
        foreach (
            [
                ...$this->configArray('id_input_keys', ['tenant_id']),
                ...$this->configArray('code_input_keys', ['tenant_code']),
                ...$this->configArray('uuid_input_keys', ['tenant_uuid']),
                ...$this->configArray('isolation_key_input_keys', ['tenant_isolation_key']),
                ...$this->configArray('domain_input_keys', ['tenant_domain']),
            ] as $key
        ) {
            if ($request->input($key) !== null && $request->input($key) !== '') {
                return true;
            }
        }

        foreach (
            [
                ...$this->configArray('id_route_keys', ['tenant_id']),
                ...$this->configArray('code_route_keys', ['tenant', 'tenant_code']),
                ...$this->configArray('uuid_route_keys', ['tenant_uuid']),
                ...$this->configArray('isolation_key_route_keys', ['tenant_isolation_key']),
            ] as $key
        ) {
            if ($request->route($key) !== null && $request->route($key) !== '') {
                return true;
            }
        }

        foreach (
            [
                ...$this->configArray('id_header_keys', ['X-Tenant-Id']),
                ...$this->configArray('code_header_keys', ['X-Tenant-Code']),
                ...$this->configArray('uuid_header_keys', ['X-Tenant-Uuid']),
                ...$this->configArray('isolation_key_header_keys', ['X-Tenant-Isolation-Key']),
                ...$this->configArray('domain_header_keys', ['X-Tenant-Domain']),
            ] as $key
        ) {
            $value = $request->headers->get($key);
            if (is_string($value) && trim($value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function toContext(
        DataRecord $tenant,
        ?string $applicationId,
        string $source,
        ?string $domain = null,
    ): CurrentTenantContext {
        $tenantId = $this->toNullableInt($tenant->get('id'));
        $tenantCode = $this->stringSignal($tenant->get('code'));
        $tenantUuid = $this->stringSignal($tenant->get('uuid'));

        if ($tenantId === null || $tenantCode === null || $tenantUuid === null) {
            throw new CurrentTenantContextResolutionException('Resolved tenant record is incomplete.');
        }

        $resolvedDomain = $domain;
        if ($resolvedDomain === null) {
            $primaryDomain = $this->tenantDomains->findPrimaryByTenant($tenantId);
            $resolvedDomain = $primaryDomain instanceof DataRecord
                ? $this->normalizeDomain($primaryDomain->get('domain'))
                : null;
        }

        return new CurrentTenantContext(
            $tenant,
            $tenantId,
            $tenantCode,
            $tenantUuid,
            $this->stringSignal($tenant->get('isolation_key')),
            $resolvedDomain,
            $this->stringSignal($tenant->get('status')),
            $this->toBool($tenant->get('is_active')),
            $applicationId,
            $source,
        );
    }

    private function stringSignal(mixed $value): ?string
    {
        if ($value === null || ! is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeDomain(mixed $value): ?string
    {
        $domain = $this->stringSignal($value);
        if ($domain === null) {
            return null;
        }

        if (str_contains($domain, '://')) {
            $parsed = parse_url($domain, PHP_URL_HOST);
            $domain = is_string($parsed) ? $parsed : $domain;
        }

        $domain = strtolower(trim($domain));
        $domain = preg_replace('/:\d+$/', '', $domain);

        return is_string($domain) && $domain !== '' ? $domain : null;
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

    /**
     * @param list<string> $fallback
     * @return list<string>
     */
    private function configArray(string $key, array $fallback): array
    {
        if (! function_exists('config')) {
            return $fallback;
        }

        try {
            $resolved = config('core.current_tenant.' . $key, $fallback);
        } catch (Throwable) {
            return $fallback;
        }

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

    private function enforceAuthenticatedTenantMatch(): bool
    {
        return (bool) config('tenant.resolution.enforce_authenticated_tenant_match', true);
    }
}
