<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Domains;

use DateTimeImmutable;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Services\Contracts\TenantDomainOwnershipVerifierInterface;
use Modules\Tenant\Services\Contracts\TenantDomainServiceInterface as TenantRules;
use Throwable;

final class TenantDomainService
{
    public function __construct(
        private readonly TenantDomainRepositoryInterface $domains,
        private readonly TenantRules $rules,
        private readonly TenantDomainOwnershipVerifierInterface $verifier,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly AuditRecorderInterface $audit,
        private readonly ErrorNormalizerInterface $errors,
    ) {}

    public function list(int $tenantId): Result
    {
        return Result::success($this->domains->listByTenant($tenantId));
    }

    public function get(int $tenantId, int|string $id): Result
    {
        $record = $this->domains->findByIdForTenant($id, $tenantId);
        return $record === null
            ? Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant domain not found.'))
            : Result::success($record);
    }

    /** @param array<string, mixed> $payload */
    public function create(int $tenantId, array $payload): Result
    {
        try {
            $domain = $this->rules->normalizeDomain((string) ($payload['domain'] ?? ''));
            if ($this->domains->findByDomain($domain) !== null) {
                return Result::failure(new Error(TenantErrorCode::DUPLICATE_DOMAIN, 'This domain is already assigned.'));
            }
            $record = $this->domains->create([
                'tenant_id' => $tenantId,
                'domain' => $domain,
                'is_primary' => false,
                'primary_marker' => null,
                'status' => 'pending',
                'verification_method' => 'dns_txt',
                'metadata' => $this->rules->normalizeMetadata($payload['metadata'] ?? null),
                'row_version' => 1,
                'created_by' => $this->currentUser->currentUserId(),
                'updated_by' => $this->currentUser->currentUserId(),
            ]);
            $this->audit('tenant.domain.created', $record, null, ['domain' => $domain, 'status' => 'pending']);
            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure($this->errors->normalize($exception, TenantErrorCode::INVALID_VALUE, ['operation' => 'tenant.domain.create']));
        }
    }

    public function requestVerification(int $tenantId, int|string $id, int $expectedVersion): Result
    {
        try {
            $record = $this->domains->findByIdForTenant($id, $tenantId);
            if ($record === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant domain not found.'));
            }
            $token = bin2hex(random_bytes(24));
            $expiresAt = now()->addMinutes(max((int) config('tenant.domains.verification_ttl_minutes', 1440), 5));
            $updated = $this->domains->updateWithVersion($id, $tenantId, $expectedVersion, [
                'status' => 'pending',
                'verification_token_hash' => hash('sha256', $token),
                'verification_expires_at' => $expiresAt,
                'verified_at' => null,
                'verified_by' => null,
                'is_primary' => false,
                'primary_marker' => null,
                'updated_by' => $this->currentUser->currentUserId(),
            ]);
            if ($updated === null) {
                return $this->versionConflict();
            }
            $prefix = (string) config('tenant.domains.verification_txt_prefix', '_autoerp-verification');
            $valuePrefix = (string) config('tenant.domains.verification_value_prefix', 'autoerp-verification=');
            return Result::success([
                'domain' => $updated,
                'challenge' => [
                    'method' => 'dns_txt',
                    'host' => $prefix.'.'.$updated->require('domain'),
                    'value' => $valuePrefix.$token,
                    'expires_at' => $expiresAt->toISOString(),
                ],
            ]);
        } catch (Throwable $exception) {
            return Result::failure($this->errors->normalize($exception, TenantErrorCode::INVALID_VALUE, ['operation' => 'tenant.domain.request_verification']));
        }
    }

    public function verify(int $tenantId, int|string $id, int $expectedVersion): Result
    {
        try {
            $record = $this->domains->findByIdForTenant($id, $tenantId);
            if ($record === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant domain not found.'));
            }
            $hash = trim((string) $record->get('verification_token_hash', ''));
            $expiresAt = $record->get('verification_expires_at');
            if ($hash === '' || $expiresAt === null || new DateTimeImmutable((string) $expiresAt) < new DateTimeImmutable()) {
                return Result::failure(new Error(TenantErrorCode::DOMAIN_NOT_VERIFIED, 'Generate a new DNS verification challenge first.'));
            }
            $domain = (string) $record->require('domain');
            if (! $this->verifier->isVerified($domain, $hash)) {
                return Result::failure(new Error(TenantErrorCode::DOMAIN_NOT_VERIFIED, 'The expected DNS TXT record was not found.'));
            }
            $updated = $this->domains->updateWithVersion($id, $tenantId, $expectedVersion, [
                'status' => 'active',
                'verified_at' => now(),
                'verified_by' => $this->currentUser->currentUserId(),
                'verification_token_hash' => null,
                'verification_expires_at' => null,
                'updated_by' => $this->currentUser->currentUserId(),
            ]);
            if ($updated === null) {
                return $this->versionConflict();
            }
            $this->audit('tenant.domain.verified', $updated, ['status' => $record->get('status')], ['status' => 'active']);
            return Result::success($updated);
        } catch (Throwable $exception) {
            return Result::failure($this->errors->normalize($exception, TenantErrorCode::INVALID_VALUE, ['operation' => 'tenant.domain.verify']));
        }
    }

    public function setPrimary(int $tenantId, int|string $id, int $expectedVersion): Result
    {
        $record = $this->domains->findByIdForTenant($id, $tenantId);
        if ($record === null) {
            return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant domain not found.'));
        }
        if ($record->get('status') !== 'active' || $record->get('verified_at') === null) {
            return Result::failure(new Error(TenantErrorCode::DOMAIN_NOT_VERIFIED, 'Only a verified active domain can be primary.'));
        }
        $updated = $this->domains->setPrimaryWithVersion(
            $id,
            $tenantId,
            $expectedVersion,
            $this->currentUser->currentUserId(),
        );
        if ($updated === null) {
            return $this->versionConflict();
        }
        $this->audit('tenant.domain.primary_changed', $updated, null, ['is_primary' => true]);
        return Result::success($updated);
    }

    public function disable(int $tenantId, int|string $id, int $expectedVersion): Result
    {
        $record = $this->domains->findByIdForTenant($id, $tenantId);
        if ($record === null) {
            return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant domain not found.'));
        }
        if ((bool) $record->get('is_primary')) {
            return Result::failure(new Error(TenantErrorCode::CONFLICT, 'Select another primary domain before disabling this domain.'));
        }
        $updated = $this->domains->updateWithVersion($id, $tenantId, $expectedVersion, [
            'status' => 'disabled', 'updated_by' => $this->currentUser->currentUserId(),
        ]);
        if ($updated === null) {
            return $this->versionConflict();
        }
        $this->audit('tenant.domain.disabled', $updated, ['status' => $record->get('status')], ['status' => 'disabled']);
        return Result::success($updated);
    }

    public function delete(int $tenantId, int|string $id, int $expectedVersion): Result
    {
        $record = $this->domains->findByIdForTenant($id, $tenantId);
        if ($record === null) {
            return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant domain not found.'));
        }
        if ((bool) $record->get('is_primary') || $record->get('status') === 'active') {
            return Result::failure(new Error(TenantErrorCode::CONFLICT, 'Active or primary domains must be disabled before removal.'));
        }
        if (! $this->domains->deleteWithVersion($id, $tenantId, $expectedVersion)) {
            return $this->versionConflict();
        }
        $this->audit('tenant.domain.deleted', $record, ['domain' => $record->get('domain')], null);
        return Result::success(true);
    }

    private function versionConflict(): Result
    {
        return Result::failure(new Error(TenantErrorCode::VERSION_CONFLICT, 'Domain changed since it was loaded. Refresh and try again.'));
    }

    private function audit(string $eventName, \Modules\Core\DTOs\DataRecord $record, ?array $old, ?array $new): void
    {
        $this->audit->record(new AuditEventData(
            eventName: $eventName, eventCategory: 'security', sourceModule: 'tenant',
            subjectType: 'tenant_domain', subjectId: (string) $record->id(), subjectReference: (string) $record->get('domain'),
            changes: ['old' => $old, 'new' => $new], tags: ['tenant', 'domain'],
        ));
    }
}
