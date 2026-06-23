<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Domains;

use Modules\Audit\Constants\AuditEventCategory;
use DateTimeImmutable;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Services\Contracts\TenantDomainOwnershipVerifierInterface;
use Modules\Tenant\Services\Contracts\TenantValueNormalizerInterface as TenantRules;
use Throwable;

final class TenantDomainService
{
    public function __construct(
        private readonly TenantDomainRepositoryInterface $domains,
        private readonly TenantRules $rules,
        private readonly TenantDomainOwnershipVerifierInterface $verifier,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly AuditRecorderInterface $audit,
        private readonly TransactionManagerInterface $transactions,
        private readonly ErrorNormalizerInterface $errors,
        private readonly ClockInterface $clock,
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
                return Result::failure(new Error(
                    TenantErrorCode::DUPLICATE_DOMAIN,
                    'This domain is already assigned.',
                ));
            }

            /** @var DataRecord $record */
            $record = $this->transactions->runInTransaction(function () use (
                $tenantId,
                $payload,
                $domain,
            ): DataRecord {
                $record = $this->domains->create([
                    'tenant_id' => $tenantId,
                    'domain' => $domain,
                    'is_primary' => false,
                    'primary_marker' => null,
                    'status' => 'pending',
                    'verification_method' => 'dns_txt',
                    'verification_failure_count' => 0,
                    'metadata' => $this->rules->normalizeMetadata($payload['metadata'] ?? null),
                    'row_version' => 1,
                    'created_by' => $this->currentUser->currentUserId(),
                    'updated_by' => $this->currentUser->currentUserId(),
                ]);

                $this->recordAudit(
                    'tenant.domain.created',
                    $record,
                    null,
                    ['domain' => $domain, 'status' => 'pending'],
                );

                return $record;
            });

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure($this->errors->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.domain.create'],
            ));
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
            $expiresAt = $this->clock->now()->modify(sprintf(
                '+%d minutes',
                max((int) config('tenant.domains.verification_ttl_minutes', 1440), 5),
            ));
            $alreadyVerified = $record->get('status') === 'active'
                && $record->get('verified_at') !== null
                && trim((string) $record->get('verified_token_hash', '')) !== '';

            $attributes = [
                'verification_token_hash' => hash('sha256', $token),
                'verification_expires_at' => $expiresAt,
                'verification_last_error' => null,
                'updated_by' => $this->currentUser->currentUserId(),
            ];
            if (! $alreadyVerified) {
                $attributes = [
                    ...$attributes,
                    'status' => 'pending',
                    'verified_at' => null,
                    'verified_by' => null,
                    'verified_token_hash' => null,
                    'is_primary' => false,
                    'primary_marker' => null,
                ];
            }

            /** @var DataRecord|null $updated */
            $updated = $this->transactions->runInTransaction(function () use (
                $id,
                $tenantId,
                $expectedVersion,
                $attributes,
                $record,
            ): ?DataRecord {
                $updated = $this->domains->updateWithVersion(
                    $id,
                    $tenantId,
                    $expectedVersion,
                    $attributes,
                );
                if ($updated === null) {
                    return null;
                }

                $this->recordAudit(
                    'tenant.domain.verification_requested',
                    $updated,
                    ['verification_expires_at' => $record->get('verification_expires_at')],
                    ['verification_expires_at' => $updated->get('verification_expires_at')],
                );

                return $updated;
            });

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
                    'expires_at' => $expiresAt->format(DATE_ATOM),
                ],
            ]);
        } catch (Throwable $exception) {
            return Result::failure($this->errors->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.domain.request_verification'],
            ));
        }
    }

    public function verify(int $tenantId, int|string $id, int $expectedVersion): Result
    {
        try {
            $record = $this->domains->findByIdForTenant($id, $tenantId);
            if ($record === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant domain not found.'));
            }

            $pendingHash = trim((string) $record->get('verification_token_hash', ''));
            $expiresAt = $record->get('verification_expires_at');
            $now = $this->clock->now();
            if (
                $pendingHash === ''
                || $expiresAt === null
                || new DateTimeImmutable((string) $expiresAt) <= $now
            ) {
                return Result::failure(new Error(
                    TenantErrorCode::DOMAIN_NOT_VERIFIED,
                    'Generate a new DNS verification challenge first.',
                ));
            }

            $verification = $this->verifier->verify(
                (string) $record->require('domain'),
                $pendingHash,
            );
            if (! $verification->verified) {
                $this->domains->recordVerificationAttempt(
                    $id,
                    $tenantId,
                    false,
                    $verification->message,
                    $now,
                );

                return Result::failure(new Error(
                    TenantErrorCode::DOMAIN_NOT_VERIFIED,
                    $verification->message ?? 'Domain ownership could not be verified.',
                    ['verification_error' => $verification->errorCode],
                ));
            }

            $revalidationDueAt = $this->nextRevalidationAt($now);
            $graceExpiresAt = $this->verificationGraceExpiresAt($now);

            /** @var DataRecord|null $updated */
            $updated = $this->transactions->runInTransaction(function () use (
                $id,
                $tenantId,
                $expectedVersion,
                $record,
                $pendingHash,
                $now,
                $revalidationDueAt,
                $graceExpiresAt,
            ): ?DataRecord {
                $updated = $this->domains->updateWithVersion($id, $tenantId, $expectedVersion, [
                    'status' => 'active',
                    'verified_at' => $now,
                    'verified_by' => $this->currentUser->currentUserId(),
                    'verified_token_hash' => $pendingHash,
                    'verification_token_hash' => null,
                    'verification_expires_at' => null,
                    'last_verification_attempt_at' => $now,
                    'last_verified_at' => $now,
                    'verification_failure_count' => 0,
                    'verification_last_error' => null,
                    'revalidation_due_at' => $revalidationDueAt,
                    'verification_grace_expires_at' => $graceExpiresAt,
                    'updated_by' => $this->currentUser->currentUserId(),
                ]);
                if ($updated === null) {
                    return null;
                }

                $this->recordAudit(
                    'tenant.domain.verified',
                    $updated,
                    ['status' => $record->get('status')],
                    ['status' => 'active'],
                );

                return $updated;
            });

            return $updated === null
                ? $this->versionConflict()
                : Result::success($updated);
        } catch (Throwable $exception) {
            return Result::failure($this->errors->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.domain.verify'],
            ));
        }
    }

    public function setPrimary(int $tenantId, int|string $id, int $expectedVersion): Result
    {
        try {
            $record = $this->domains->findByIdForTenant($id, $tenantId);
            if ($record === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant domain not found.'));
            }
            if ($record->get('status') !== 'active' || $record->get('verified_at') === null) {
                return Result::failure(new Error(
                    TenantErrorCode::DOMAIN_NOT_VERIFIED,
                    'Only a verified active domain can be primary.',
                ));
            }

            /** @var DataRecord|null $updated */
            $updated = $this->transactions->runInTransaction(function () use (
                $id,
                $tenantId,
                $expectedVersion,
            ): ?DataRecord {
                $updated = $this->domains->setPrimaryWithVersion(
                    $id,
                    $tenantId,
                    $expectedVersion,
                    $this->currentUser->currentUserId(),
                );
                if ($updated === null) {
                    return null;
                }

                $this->recordAudit(
                    'tenant.domain.primary_changed',
                    $updated,
                    null,
                    ['is_primary' => true],
                );

                return $updated;
            });

            return $updated === null
                ? $this->versionConflict()
                : Result::success($updated);
        } catch (Throwable $exception) {
            return Result::failure($this->errors->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.domain.set_primary'],
            ));
        }
    }

    public function disable(int $tenantId, int|string $id, int $expectedVersion): Result
    {
        try {
            $record = $this->domains->findByIdForTenant($id, $tenantId);
            if ($record === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant domain not found.'));
            }
            if ((bool) $record->get('is_primary')) {
                return Result::failure(new Error(
                    TenantErrorCode::CONFLICT,
                    'Select another primary domain before disabling this domain.',
                ));
            }

            /** @var DataRecord|null $updated */
            $updated = $this->transactions->runInTransaction(function () use (
                $id,
                $tenantId,
                $expectedVersion,
                $record,
            ): ?DataRecord {
                $updated = $this->domains->updateWithVersion($id, $tenantId, $expectedVersion, [
                    'status' => 'disabled',
                    'updated_by' => $this->currentUser->currentUserId(),
                ]);
                if ($updated === null) {
                    return null;
                }

                $this->recordAudit(
                    'tenant.domain.disabled',
                    $updated,
                    ['status' => $record->get('status')],
                    ['status' => 'disabled'],
                );

                return $updated;
            });

            return $updated === null
                ? $this->versionConflict()
                : Result::success($updated);
        } catch (Throwable $exception) {
            return Result::failure($this->errors->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.domain.disable'],
            ));
        }
    }

    public function delete(int $tenantId, int|string $id, int $expectedVersion): Result
    {
        try {
            $record = $this->domains->findByIdForTenant($id, $tenantId);
            if ($record === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant domain not found.'));
            }
            if ((bool) $record->get('is_primary') || $record->get('status') === 'active') {
                return Result::failure(new Error(
                    TenantErrorCode::CONFLICT,
                    'Active or primary domains must be disabled before removal.',
                ));
            }

            $deleted = $this->transactions->runInTransaction(function () use (
                $id,
                $tenantId,
                $expectedVersion,
                $record,
            ): bool {
                if (! $this->domains->deleteWithVersion($id, $tenantId, $expectedVersion)) {
                    return false;
                }

                $this->recordAudit(
                    'tenant.domain.deleted',
                    $record,
                    ['domain' => $record->get('domain')],
                    null,
                );

                return true;
            });

            return $deleted
                ? Result::success(true)
                : $this->versionConflict();
        } catch (Throwable $exception) {
            return Result::failure($this->errors->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.domain.delete'],
            ));
        }
    }

    private function nextRevalidationAt(DateTimeImmutable $now): DateTimeImmutable
    {
        return $now->modify(sprintf(
            '+%d hours',
            max((int) config('tenant.domains.revalidation_interval_hours', 24), 1),
        ));
    }

    private function verificationGraceExpiresAt(DateTimeImmutable $now): DateTimeImmutable
    {
        return $now->modify(sprintf(
            '+%d days',
            max((int) config('tenant.domains.verification_grace_days', 7), 1),
        ));
    }

    private function versionConflict(): Result
    {
        return Result::failure(new Error(
            TenantErrorCode::VERSION_CONFLICT,
            'Domain changed since it was loaded. Refresh and try again.',
        ));
    }

    /** @param array<string, mixed>|null $old @param array<string, mixed>|null $new */
    private function recordAudit(
        string $eventName,
        DataRecord $record,
        ?array $old,
        ?array $new,
    ): void {
        $this->audit->record(new AuditEventData(
            eventName: $eventName,
            eventCategory: AuditEventCategory::SECURITY,
            sourceModule: 'tenant',
            subjectType: 'tenant_domain',
            subjectId: (string) $record->id(),
            subjectReference: (string) $record->get('domain'),
            changes: ['old' => $old, 'new' => $new],
            tags: ['tenant', 'domain'],
        ));
    }
}
