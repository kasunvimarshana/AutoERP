<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\UseCases\Domains;

use DateTimeImmutable;
use Modules\Core\Application\Contracts\ErrorNormalizerInterface;
use Modules\Core\Application\Contracts\TransactionManagerInterface;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Tenant\Application\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Application\Support\TenantContext;
use Modules\Tenant\Domain\Constants\TenantErrorCode;
use Modules\Tenant\Domain\Contracts\TenantDomainServiceInterface as TenantBusinessRules;
use Throwable;

final class TenantDomainService
{
    public function __construct(
        private readonly TenantDomainRepositoryInterface $domains,
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantBusinessRules $domain,
        private readonly TenantContext $tenantContext,
        private readonly TransactionManagerInterface $transactions,
        private readonly ErrorNormalizerInterface $errorNormalizer,
    ) {}

    public function listByTenant(int|string $tenantId): Result
    {
        try {
            $resolvedTenantId = $this->tenantContext->resolveTenantId($this->toNullableInt($tenantId));

            return Result::success($this->domains->listByTenant($resolvedTenantId));
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.domains.list'],
            ));
        }
    }

    public function get(int|string $id): Result
    {
        try {
            $record = $this->domains->findById($id);
            if ($record === null || ! $this->isRecordInTenantScope($record)) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant domain not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.domains.get', 'domain_id' => (string) $id],
            ));
        }
    }

    public function create(array $payload): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($payload): Result {
                $tenantId = $this->tenantContext->resolveTenantId($this->toNullableInt($payload['tenant_id'] ?? null));
                if ($this->tenants->findById($tenantId) === null) {
                    return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant not found.'));
                }

                $domain = $this->domain->normalizeDomain((string) ($payload['domain'] ?? ''));
                $byDomain = $this->domains->findByDomain($domain);
                if ($byDomain !== null) {
                    return Result::failure(
                        new Error(TenantErrorCode::CONFLICT, 'Domain already assigned to another tenant.'),
                    );
                }

                $isPrimary = isset($payload['is_primary']) ? (bool) $payload['is_primary'] : false;
                if ($isPrimary) {
                    $this->domains->clearPrimaryForTenant($tenantId);
                }

                $isVerified = isset($payload['is_verified']) ? (bool) $payload['is_verified'] : false;
                $verifiedAt = $isVerified
                    ? $this->normalizeVerifiedAt(
                        isset($payload['verified_at']) ? (string) $payload['verified_at'] : null,
                    )
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
            });
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.domains.create'],
            ));
        }
    }

    public function update(int|string $id, array $payload): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($id, $payload): Result {
                $existing = $this->domains->findById($id);
                if ($existing === null || ! $this->isRecordInTenantScope($existing)) {
                    return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant domain not found.'));
                }

                $tenantId = (int) $existing->require('tenant_id');
                $domain = $this->domain->normalizeDomain((string) ($payload['domain'] ?? $existing->require('domain')));
                $byDomain = $this->domains->findByDomain($domain);
                if ($byDomain !== null && (string) $byDomain->id() !== (string) $existing->id()) {
                    return Result::failure(
                        new Error(TenantErrorCode::CONFLICT, 'Domain already assigned to another tenant.'),
                    );
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
                    ? $this->normalizeVerifiedAt(
                        isset($payload['verified_at'])
                            ? (string) $payload['verified_at']
                            : ($existing->get('verified_at') !== null
                                ? (string) $existing->get('verified_at')
                                : null),
                    )
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
            });
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.domains.update', 'domain_id' => (string) $id],
            ));
        }
    }

    public function delete(int|string $id): Result
    {
        try {
            return $this->transactions->runInTransaction(function () use ($id): Result {
                $existing = $this->domains->findById($id);
                if ($existing === null || ! $this->isRecordInTenantScope($existing)) {
                    return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant domain not found.'));
                }

                return Result::success($this->domains->delete($id));
            });
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.domains.delete', 'domain_id' => (string) $id],
            ));
        }
    }

    private function normalizeVerifiedAt(?string $value): string
    {
        $candidate = $this->domain->normalizeOptionalText($value);
        if ($candidate === null) {
            return now()->format('Y-m-d H:i:s');
        }

        return (new DateTimeImmutable($candidate))->format('Y-m-d H:i:s');
    }

    private function isRecordInTenantScope(DataRecord $record): bool
    {
        return (int) $record->require('tenant_id') === $this->tenantContext->requireTenantId();
    }

    private function toNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }
}
