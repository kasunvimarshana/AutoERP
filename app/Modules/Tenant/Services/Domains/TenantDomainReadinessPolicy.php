<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Domains;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Core\DTOs\DataRecord;
use Modules\Tenant\Constants\TenantDomainCheckStatus;
use Modules\Tenant\Constants\TenantDomainOperationalStatus;
use Modules\Tenant\Constants\TenantDomainOwnershipStatus;
use Modules\Tenant\Constants\TenantDomainStatus;

final class TenantDomainReadinessPolicy
{
    /** @param array<string, mixed>|DataRecord $domain */
    public function isReady(array|DataRecord $domain): bool
    {
        $values = $domain instanceof DataRecord ? $domain->toArray() : $domain;

        return ($values['status'] ?? null) === TenantDomainStatus::ACTIVE
            && ($values['ownership_status'] ?? null) === TenantDomainOwnershipStatus::VERIFIED
            && ($values['verified_at'] ?? null) !== null
            && ($values['routing_status'] ?? null) === TenantDomainCheckStatus::READY
            && ($values['tls_status'] ?? null) === TenantDomainCheckStatus::READY
            && ($values['reachability_status'] ?? null) === TenantDomainCheckStatus::READY
            && ($values['operational_status'] ?? null) === TenantDomainOperationalStatus::READY;
    }

    /** @param array<string, mixed> $attributes */
    public function assertValid(array $attributes): void
    {
        $status = (string) ($attributes['status'] ?? '');
        $ownership = (string) ($attributes['ownership_status'] ?? '');
        $routing = (string) ($attributes['routing_status'] ?? '');
        $tls = (string) ($attributes['tls_status'] ?? '');
        $reachability = (string) ($attributes['reachability_status'] ?? '');
        $operational = (string) ($attributes['operational_status'] ?? '');

        if (! in_array($status, TenantDomainStatus::values(), true)
            || ! in_array($ownership, TenantDomainOwnershipStatus::values(), true)
            || ! in_array($routing, TenantDomainCheckStatus::values(), true)
            || ! in_array($tls, TenantDomainCheckStatus::values(), true)
            || ! in_array($reachability, TenantDomainCheckStatus::values(), true)
            || ! in_array($operational, TenantDomainOperationalStatus::values(), true)
        ) {
            throw new InvalidArgumentException('Tenant domain state contains an unsupported status value.');
        }

        $verifiedAt = $attributes['verified_at'] ?? null;
        $verifiedTokenHash = trim((string) ($attributes['verified_token_hash'] ?? ''));
        $operationalErrorCode = trim((string) ($attributes['operational_error_code'] ?? ''));
        $operationalErrorMessage = trim((string) ($attributes['operational_error_message'] ?? ''));
        $operationalClaimToken = trim((string) ($attributes['operational_claim_token'] ?? ''));
        $operationalClaimedAt = $attributes['operational_claimed_at'] ?? null;
        $operationalLease = $attributes['operational_claim_lease_expires_at'] ?? null;
        $revalidationClaimToken = trim((string) ($attributes['revalidation_claim_token'] ?? ''));
        $revalidationClaimedAt = $attributes['revalidation_claimed_at'] ?? null;
        $revalidationLease = $attributes['revalidation_claim_lease_expires_at'] ?? null;

        if ($ownership === TenantDomainOwnershipStatus::VERIFIED) {
            if ($verifiedAt === null || $verifiedTokenHash === '') {
                throw new InvalidArgumentException('Verified tenant domains require durable ownership evidence.');
            }
        } elseif ($verifiedAt !== null) {
            throw new InvalidArgumentException('Only ownership-verified tenant domains may retain verified_at.');
        }

        if ($status === TenantDomainStatus::ACTIVE && $operational !== TenantDomainOperationalStatus::READY) {
            throw new InvalidArgumentException('Active tenant domains must be operationally ready.');
        }

        if ($operational === TenantDomainOperationalStatus::READY) {
            if ($status !== TenantDomainStatus::ACTIVE
                || $ownership !== TenantDomainOwnershipStatus::VERIFIED
                || $verifiedAt === null
                || $routing !== TenantDomainCheckStatus::READY
                || $tls !== TenantDomainCheckStatus::READY
                || $reachability !== TenantDomainCheckStatus::READY
            ) {
                throw new InvalidArgumentException('Operational readiness requires verified ownership, routing, TLS and reachability.');
            }
            if ($operationalErrorCode !== '' || $operationalErrorMessage !== '') {
                throw new InvalidArgumentException('Operationally ready tenant domains cannot retain operational failures.');
            }
        }

        if ($status === TenantDomainStatus::DISABLED && $operational !== TenantDomainOperationalStatus::DISABLED) {
            throw new InvalidArgumentException('Disabled tenant domains must use the disabled operational state.');
        }
        if ($operational === TenantDomainOperationalStatus::DISABLED && $status !== TenantDomainStatus::DISABLED) {
            throw new InvalidArgumentException('Only disabled tenant domains may use the disabled operational state.');
        }

        $this->assertClaimShape(
            'operational',
            $operationalClaimToken,
            $operationalClaimedAt,
            $operationalLease,
        );
        $this->assertClaimShape(
            'revalidation',
            $revalidationClaimToken,
            $revalidationClaimedAt,
            $revalidationLease,
        );
    }

    /** @param Builder<\Modules\Tenant\Models\TenantDomainModel> $query */
    public function applyReadyScope(Builder $query, string $table = 'tenant_domains'): Builder
    {
        return $query
            ->where("{$table}.status", TenantDomainStatus::ACTIVE)
            ->where("{$table}.ownership_status", TenantDomainOwnershipStatus::VERIFIED)
            ->whereNotNull("{$table}.verified_at")
            ->where("{$table}.routing_status", TenantDomainCheckStatus::READY)
            ->where("{$table}.tls_status", TenantDomainCheckStatus::READY)
            ->where("{$table}.reachability_status", TenantDomainCheckStatus::READY)
            ->where("{$table}.operational_status", TenantDomainOperationalStatus::READY);
    }

    private function assertClaimShape(string $name, string $token, mixed $claimedAt, mixed $lease): void
    {
        $hasToken = $token !== '';
        $hasClaimedAt = $claimedAt !== null;
        $hasLease = $lease !== null;

        if (($hasToken || $hasClaimedAt || $hasLease) && ! ($hasToken && $hasClaimedAt && $hasLease)) {
            throw new InvalidArgumentException("Tenant domain {$name} claims require token, claimed_at and lease expiry together.");
        }
    }
}
