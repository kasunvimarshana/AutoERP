<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\CurrentTenantContextResolverInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\TenantUserAccessCheckerInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\DTOs\CurrentTenantContext;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Exceptions\CurrentTenantContextResolutionException;
use Modules\Tenant\Constants\TenantDomainOperationalStatus;
use Modules\Tenant\Constants\TenantDomainOwnershipStatus;
use Modules\Tenant\Constants\TenantDomainStatus;
use Modules\Tenant\Constants\TenantStatus;
use Modules\Tenant\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Services\Hosts\PlatformHostPolicy;

final class CurrentTenantContextResolver implements CurrentTenantContextResolverInterface
{
    public function __construct(
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantDomainRepositoryInterface $domains,
        private readonly TenantUserAccessCheckerInterface $userAccess,
        private readonly ClockInterface $clock,
        private readonly PlatformHostPolicy $hosts,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {}

    public function resolve(Request $request): ?CurrentTenantContext
    {
        $applicationId = $this->currentUser->currentApplicationId();
        $host = $this->hosts->normalize($request->getHost());
        $hostContext = $this->fromHost($host, $applicationId);

        if ($host !== null && ! $this->hosts->isCentralHost($host) && $hostContext === null) {
            throw new CurrentTenantContextResolutionException(
                'The request host is not assigned to an active verified tenant.',
            );
        }

        $selectedContext = $this->fromSelectionHeaders($request, $applicationId);
        if ($hostContext !== null && $selectedContext !== null && $hostContext->tenantId() !== $selectedContext->tenantId()) {
            throw new CurrentTenantContextResolutionException('The requested host and selected tenant do not match.');
        }
        if ($selectedContext !== null) {
            return $selectedContext;
        }
        if ($hostContext !== null) {
            return $hostContext;
        }

        return $this->localFallback($applicationId);
    }

    public function hasAccess(Request $request, CurrentTenantContext $context): bool
    {
        $userId = $this->authenticatedUserId($request);
        $tokenTenantId = $this->authenticatedTokenTenantId();

        if ($userId === null || $tokenTenantId !== $context->tenantId()) {
            return false;
        }

        return $this->userAccess->isActiveTenantUser($userId, $context->tenantId());
    }

    private function authenticatedTokenTenantId(): ?int
    {
        $context = $this->currentUser->current();
        if ($context === null) {
            return null;
        }

        return $this->positiveInt($context->tokenPayload()['tenant_id'] ?? null);
    }

    private function fromHost(?string $host, ?string $applicationId): ?CurrentTenantContext
    {
        if ($host === null || $this->hosts->isCentralHost($host)) {
            return null;
        }
        $domain = $this->domains->findByDomainFromControlPlane($host);
        if (
            $domain === null
            || $domain->get('status') !== TenantDomainStatus::ACTIVE
            || $domain->get('ownership_status') !== TenantDomainOwnershipStatus::VERIFIED
            || $domain->get('operational_status') !== TenantDomainOperationalStatus::READY
            || $domain->get('verified_at') === null
        ) {
            return null;
        }
        $tenantId = $this->positiveInt($domain->get('tenant_id'));
        $tenant = $tenantId === null ? null : $this->findTenantById($tenantId);
        return $tenant === null ? null : $this->context($tenant, $applicationId, 'verified_host', $host);
    }

    private function fromSelectionHeaders(Request $request, ?string $applicationId): ?CurrentTenantContext
    {
        $idHeader = (string) config('tenant.resolution.selection_headers.id', 'X-Tenant-Id');
        $codeHeader = (string) config('tenant.resolution.selection_headers.code', 'X-Tenant-Code');
        $idValue = $request->headers->get($idHeader);
        $codeValue = $request->headers->get($codeHeader);
        if (($idValue === null || $idValue === '') && ($codeValue === null || trim((string) $codeValue) === '')) {
            return null;
        }

        $byId = null;
        if ($idValue !== null && $idValue !== '') {
            $tenantId = $this->positiveInt($idValue);
            if ($tenantId === null) {
                throw new CurrentTenantContextResolutionException('The selected tenant identifier is invalid.');
            }
            $byId = $this->findTenantById($tenantId);
        }
        $byCode = $codeValue === null || trim((string) $codeValue) === ''
            ? null : $this->findTenantByCode((string) $codeValue);

        if (($byId === null && $idValue !== null && $idValue !== '') || ($byCode === null && $codeValue !== null && trim((string) $codeValue) !== '')) {
            throw new CurrentTenantContextResolutionException('The selected tenant could not be resolved.');
        }
        if ($byId !== null && $byCode !== null && (int) $byId->id() !== (int) $byCode->id()) {
            throw new CurrentTenantContextResolutionException('Tenant selection headers resolve to different tenants.');
        }
        return $this->context($byId ?? $byCode, $applicationId, 'selection_header');
    }

    private function localFallback(?string $applicationId): ?CurrentTenantContext
    {
        if (! (bool) config('tenant.resolution.local_fallback_enabled', false) || ! app()->environment(['local', 'testing'])) {
            return null;
        }
        $domainValue = trim((string) config('tenant.resolution.local_fallback_domain', ''));
        if ($domainValue !== '') {
            $domain = $this->domains->findByDomainFromControlPlane($domainValue);
            $tenantId = $domain === null ? null : $this->positiveInt($domain->get('tenant_id'));
            $tenant = $tenantId === null ? null : $this->findTenantById($tenantId);
            if ($tenant !== null) {
                return $this->context($tenant, $applicationId, 'local_fallback', $domainValue);
            }
        }
        $code = trim((string) config('tenant.resolution.local_fallback_tenant_code', ''));
        $tenant = $code === '' ? null : $this->findTenantByCode($code);
        return $tenant === null ? null : $this->context($tenant, $applicationId, 'local_fallback');
    }

    private function context(DataRecord $tenant, ?string $applicationId, string $source, ?string $domain = null): CurrentTenantContext
    {
        $tenantId = $this->positiveInt($tenant->get('id'));
        $code = trim((string) $tenant->get('code', ''));
        $uuid = trim((string) $tenant->get('uuid', ''));
        $status = strtolower(trim((string) $tenant->get('status', '')));
        if ($tenantId === null || $code === '' || $uuid === '') {
            throw new CurrentTenantContextResolutionException('Resolved tenant record is incomplete.');
        }
        if (! TenantStatus::allowsRuntimeAccess($status)) {
            throw new CurrentTenantContextResolutionException('The selected tenant is not active.');
        }
        $this->assertCurrentSubscription($tenant->get('current_subscription'), $this->clock->now());
        if ($domain === null) {
            $primary = $this->domains->findPrimaryByTenant($tenantId);
            $domain = $primary === null ? null : (string) $primary->get('domain');
        }
        return new CurrentTenantContext($tenant, $tenantId, $code, $uuid, $domain, $status, $applicationId, $source);
    }

    private function assertCurrentSubscription(mixed $value, DateTimeImmutable $now): void
    {
        if (! is_array($value)) {
            throw new CurrentTenantContextResolutionException('The selected tenant has no current subscription.');
        }

        $status = strtolower(trim((string) ($value['status'] ?? '')));
        $startsAt = $this->dateTime($value['starts_at'] ?? null);
        if ($startsAt === null || $startsAt > $now) {
            throw new CurrentTenantContextResolutionException('The selected tenant subscription is not active yet.');
        }

        if ($status === 'trial') {
            $trialEndsAt = $this->dateTime($value['trial_ends_at'] ?? null);
            if ($trialEndsAt === null || $trialEndsAt <= $now) {
                throw new CurrentTenantContextResolutionException('The selected tenant trial has expired.');
            }

            return;
        }

        if ($status !== 'active') {
            throw new CurrentTenantContextResolutionException('The selected tenant subscription is not active.');
        }

        $endsAt = $this->dateTime($value['ends_at'] ?? null);
        if ($endsAt !== null && $endsAt <= $now) {
            throw new CurrentTenantContextResolutionException('The selected tenant subscription has expired.');
        }
    }

    private function dateTime(mixed $value): ?DateTimeImmutable
    {
        return $value === null || trim((string) $value) === ''
            ? null
            : new DateTimeImmutable((string) $value);
    }

    private function findTenantById(int $tenantId): ?DataRecord
    {
        return $this->executionContext->runAsControlPlane(
            fn (): ?DataRecord => $this->tenants->findById($tenantId),
        );
    }

    private function findTenantByCode(string $code): ?DataRecord
    {
        return $this->executionContext->runAsControlPlane(
            fn (): ?DataRecord => $this->tenants->findByCode($code),
        );
    }

    private function authenticatedUserId(Request $request): ?int
    {
        $id = $this->currentUser->currentUserId();
        if ($id !== null && $id > 0) {
            return $id;
        }
        $user = $request->user();
        return $user instanceof Authenticatable ? $this->positiveInt($user->getAuthIdentifier()) : null;
    }

    private function positiveInt(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }
        $value = (int) $value;
        return $value > 0 ? $value : null;
    }
}
