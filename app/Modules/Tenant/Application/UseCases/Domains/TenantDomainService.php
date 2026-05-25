<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\UseCases\Domains;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Tenant\Application\Contracts\UseCases\Domains\TenantDomainServiceInterface;
use Modules\Tenant\Application\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Domain\Constants\TenantErrorCode;
use Modules\Tenant\Domain\Contracts\TenantDomainServiceInterface as TenantBusinessRules;
use Throwable;

final class TenantDomainService implements TenantDomainServiceInterface
{
    public function __construct(
        private readonly TenantDomainRepositoryInterface $domains,
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantBusinessRules $domain,
    ) {
    }

    public function listByTenant(int|string $tenantId): Result
    {
        try {
            return Result::success($this->domains->listByTenant($tenantId));
        } catch (Throwable $exception) {
            return Result::failure(new Error(TenantErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $record = $this->domains->findById($id);
            if ($record === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant domain not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(TenantErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function create(array $payload): Result
    {
        try {
            $tenantId = (int) ($payload['tenant_id'] ?? 0);
            if ($tenantId < 1 || $this->tenants->findById($tenantId) === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant not found.'));
            }

            $domain = $this->domain->normalizeDomain((string) ($payload['domain'] ?? ''));
            $byDomain = $this->domains->findByDomain($domain);
            if ($byDomain !== null) {
                return Result::failure(new Error(TenantErrorCode::CONFLICT, 'Domain already assigned to another tenant.'));
            }

            $isPrimary = isset($payload['is_primary']) ? (bool) $payload['is_primary'] : false;
            if ($isPrimary) {
                $this->domains->clearPrimaryForTenant($tenantId);
            }

            $isVerified = isset($payload['is_verified']) ? (bool) $payload['is_verified'] : false;
            $verifiedAt = $isVerified
                ? (isset($payload['verified_at']) ? (string) $payload['verified_at'] : now()->format('Y-m-d H:i:s'))
                : null;

            $record = $this->domains->create([
                'tenant_id' => $tenantId,
                'domain' => $domain,
                'is_primary' => $isPrimary,
                'is_verified' => $isVerified,
                'verified_at' => $verifiedAt,
                'metadata' => $this->domain->normalizeMetadata($payload['metadata'] ?? null),
                'row_version' => 1,
            ]);

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(TenantErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function update(int|string $id, array $payload): Result
    {
        try {
            $existing = $this->domains->findById($id);
            if ($existing === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant domain not found.'));
            }

            $tenantId = (int) $existing->require('tenant_id');
            $domain = $this->domain->normalizeDomain((string) ($payload['domain'] ?? $existing->require('domain')));
            $byDomain = $this->domains->findByDomain($domain);
            if ($byDomain !== null && (string) $byDomain->id() !== (string) $existing->id()) {
                return Result::failure(new Error(TenantErrorCode::CONFLICT, 'Domain already assigned to another tenant.'));
            }

            $isPrimary = array_key_exists('is_primary', $payload)
                ? (bool) $payload['is_primary']
                : (bool) $existing->get('is_primary', false);

            if ($isPrimary) {
                $this->domains->clearPrimaryForTenant($tenantId);
            }

            $isVerified = array_key_exists('is_verified', $payload)
                ? (bool) $payload['is_verified']
                : (bool) $existing->get('is_verified', false);

            $verifiedAt = $isVerified
                ? (isset($payload['verified_at'])
                    ? (string) $payload['verified_at']
                    : ($existing->get('verified_at') !== null
                        ? (string) $existing->get('verified_at')
                        : now()->format('Y-m-d H:i:s')))
                : null;

            $record = $this->domains->update($id, [
                'domain' => $domain,
                'is_primary' => $isPrimary,
                'is_verified' => $isVerified,
                'verified_at' => $verifiedAt,
                'metadata' => array_key_exists('metadata', $payload)
                    ? $this->domain->normalizeMetadata($payload['metadata'])
                    : $this->domain->normalizeMetadata($existing->get('metadata')),
                'row_version' => ((int) $existing->get('row_version', 1)) + 1,
            ]);

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(TenantErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function delete(int|string $id): Result
    {
        try {
            if ($this->domains->findById($id) === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant domain not found.'));
            }

            return Result::success($this->domains->delete($id));
        } catch (Throwable $exception) {
            return Result::failure(new Error(TenantErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
