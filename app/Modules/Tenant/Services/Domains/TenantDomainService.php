<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Domains;

use DateTimeImmutable;
use Illuminate\Database\QueryException;
use Modules\Audit\Constants\AuditEventCategory;
use Modules\Audit\Contracts\AuditRecorderInterface;
use Modules\Audit\Data\AuditEventData;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\Contracts\TransactionManagerInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantDomainCheckStatus;
use Modules\Tenant\Constants\TenantDomainOperationalStatus;
use Modules\Tenant\Constants\TenantDomainOwnershipStatus;
use Modules\Tenant\Constants\TenantDomainStatus;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Jobs\VerifyTenantDomainOwnership;
use Modules\Tenant\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Services\Contracts\TenantValueNormalizerInterface as TenantRules;
use Throwable;

final class TenantDomainService
{
    public function __construct(
        private readonly TenantDomainRepositoryInterface $domains,
        private readonly TenantRules $rules,
        private readonly CurrentUserContextAccessorInterface $currentUser,
        private readonly AuditRecorderInterface $audit,
        private readonly TransactionManagerInterface $transactions,
        private readonly ErrorNormalizerInterface $errors,
        private readonly ClockInterface $clock,
    ) {}

    /** @param array<string, mixed> $filters */
    public function list(int $tenantId, array $filters = []): Result
    {
        $defaultPerPage = max(1, (int) config('tenant.pagination.default_per_page', 20));
        $maximumPerPage = max($defaultPerPage, (int) config('tenant.pagination.max_per_page', 100));

        return Result::success($this->domains->pageByTenant(
            $tenantId,
            $this->optionalString($filters['search'] ?? null),
            $this->optionalString($filters['status'] ?? null),
            $this->optionalString($filters['ownership_status'] ?? null),
            $this->optionalString($filters['operational_status'] ?? null),
            max(1, min((int) ($filters['per_page'] ?? $defaultPerPage), $maximumPerPage)),
            max(1, (int) ($filters['page'] ?? 1)),
        ));
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
            $actorId = $this->currentUser->currentUserId();

            /** @var DataRecord $record */
            $record = $this->transactions->runInTransaction(function () use ($tenantId, $domain, $actorId): DataRecord {
                $record = $this->domains->create([
                    'tenant_id' => $tenantId,
                    'domain' => $domain,
                    'status' => TenantDomainStatus::PENDING,
                    'ownership_status' => TenantDomainOwnershipStatus::PENDING,
                    'routing_status' => TenantDomainCheckStatus::PENDING,
                    'tls_status' => TenantDomainCheckStatus::PENDING,
                    'reachability_status' => TenantDomainCheckStatus::PENDING,
                    'operational_status' => TenantDomainOperationalStatus::PENDING,
                    'verification_method' => 'dns_txt',
                    'verification_failure_count' => 0,
                    'row_version' => 1,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);

                $this->recordAudit(
                    'tenant.domain.created',
                    $record,
                    null,
                    [
                        'domain' => $domain,
                        'status' => TenantDomainStatus::PENDING,
                        'ownership_status' => TenantDomainOwnershipStatus::PENDING,
                    ],
                );

                return $record;
            });

            return Result::success($record);
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                return Result::failure(new Error(
                    TenantErrorCode::DUPLICATE_DOMAIN,
                    'This domain is already assigned.',
                ));
            }

            return Result::failure($this->normalizeFailure($exception, 'tenant.domain.create'));
        } catch (Throwable $exception) {
            return Result::failure($this->normalizeFailure($exception, 'tenant.domain.create'));
        }
    }

    public function requestVerification(int $tenantId, int|string $id, int $expectedVersion): Result
    {
        try {
            $record = $this->domains->findByIdForTenant($id, $tenantId);
            if ($record === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant domain not found.'));
            }
            if ($record->get('operational_status') === TenantDomainOperationalStatus::READY) {
                return Result::failure(new Error(
                    TenantErrorCode::CONFLICT,
                    'This domain is already operationally verified. Disable it before starting a new ownership challenge.',
                ));
            }

            $token = bin2hex(random_bytes(24));
            $tokenHash = hash('sha256', $token);
            $expiresAt = $this->clock->now()->modify(sprintf(
                '+%d minutes',
                max((int) config('tenant.domains.verification_ttl_minutes', 1440), 5),
            ));
            $actorId = $this->currentUser->currentUserId();

            /** @var DataRecord|null $updated */
            $updated = $this->transactions->runInTransaction(function () use (
                $id,
                $tenantId,
                $expectedVersion,
                $record,
                $tokenHash,
                $expiresAt,
                $actorId,
            ): ?DataRecord {
                $updated = $this->domains->updateWithVersion($id, $tenantId, $expectedVersion, [
                    'status' => TenantDomainStatus::PENDING,
                    'ownership_status' => TenantDomainOwnershipStatus::PENDING,
                    'verification_token_hash' => $tokenHash,
                    'verified_token_hash' => null,
                    'verification_expires_at' => $expiresAt,
                    'verified_at' => null,
                    'verified_by' => null,
                    'last_verified_at' => null,
                    'verification_failure_count' => 0,
                    'verification_error_code' => null,
                    'verification_error_message' => null,
                    'revalidation_due_at' => null,
                    'verification_grace_expires_at' => null,
                    'operational_probe_token' => null,
                    'operational_probe_token_hash' => null,
                    'routing_status' => TenantDomainCheckStatus::PENDING,
                    'tls_status' => TenantDomainCheckStatus::PENDING,
                    'reachability_status' => TenantDomainCheckStatus::PENDING,
                    'operational_status' => TenantDomainOperationalStatus::PENDING,
                    'last_operational_check_at' => null,
                    'operational_retry_at' => null,
                    'tls_expires_at' => null,
                    'operational_error_code' => null,
                    'operational_error_message' => null,
                    'updated_by' => $actorId,
                ]);
                if ($updated === null) {
                    return null;
                }

                $this->recordAudit(
                    'tenant.domain.verification_requested',
                    $updated,
                    [
                        'ownership_status' => $record->get('ownership_status'),
                        'verification_expires_at' => $record->get('verification_expires_at'),
                    ],
                    [
                        'ownership_status' => TenantDomainOwnershipStatus::PENDING,
                        'verification_expires_at' => $expiresAt->format(DATE_ATOM),
                    ],
                );

                return $updated;
            });

            if ($updated === null) {
                return $this->versionConflict();
            }

            return Result::success([
                'domain' => $updated,
                'challenge' => [
                    'method' => 'dns_txt',
                    'host' => $this->verificationHost((string) $updated->require('domain')),
                    'value' => $this->verificationValue($token),
                    'expires_at' => $expiresAt->format(DATE_ATOM),
                ],
            ]);
        } catch (Throwable $exception) {
            return Result::failure($this->normalizeFailure(
                $exception,
                'tenant.domain.request_verification',
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
            $expiresAt = $this->asDateTime($record->get('verification_expires_at'));
            if ($pendingHash === '' || $expiresAt === null || $expiresAt <= $this->clock->now()) {
                return Result::failure(new Error(
                    TenantErrorCode::DOMAIN_NOT_VERIFIED,
                    'Generate a new DNS verification challenge first.',
                ));
            }

            $actorId = $this->currentUser->currentUserId();
            /** @var DataRecord|null $updated */
            $updated = $this->transactions->runInTransaction(function () use (
                $id,
                $tenantId,
                $expectedVersion,
                $record,
                $actorId,
            ): ?DataRecord {
                $updated = $this->domains->updateWithVersion($id, $tenantId, $expectedVersion, [
                    'ownership_status' => TenantDomainOwnershipStatus::CHECKING,
                    'verification_error_code' => null,
                    'verification_error_message' => null,
                    'last_verification_attempt_at' => $this->clock->now(),
                    'updated_by' => $actorId,
                ]);
                if ($updated === null) {
                    return null;
                }

                $this->recordAudit(
                    'tenant.domain.verification_queued',
                    $updated,
                    ['ownership_status' => $record->get('ownership_status')],
                    ['ownership_status' => TenantDomainOwnershipStatus::CHECKING],
                );

                return $updated;
            });

            if ($updated === null) {
                return $this->versionConflict();
            }

            VerifyTenantDomainOwnership::dispatch(
                $tenantId,
                (int) $updated->id(),
                $pendingHash,
                $actorId,
            )->afterCommit();

            return Result::success($updated);
        } catch (Throwable $exception) {
            return Result::failure($this->normalizeFailure($exception, 'tenant.domain.verify'));
        }
    }

    public function setPrimary(int $tenantId, int|string $id, int $expectedVersion): Result
    {
        try {
            $record = $this->domains->findByIdForTenant($id, $tenantId);
            if ($record === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant domain not found.'));
            }
            if (
                $record->get('status') !== TenantDomainStatus::ACTIVE
                || $record->get('ownership_status') !== TenantDomainOwnershipStatus::VERIFIED
                || $record->get('operational_status') !== TenantDomainOperationalStatus::READY
            ) {
                return Result::failure(new Error(
                    TenantErrorCode::DOMAIN_NOT_VERIFIED,
                    'Only an ownership-verified, operationally ready domain can be primary.',
                ));
            }

            /** @var DataRecord|null $updated */
            $updated = $this->transactions->runInTransaction(function () use ($id, $tenantId, $expectedVersion): ?DataRecord {
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

            return $updated === null ? $this->versionConflict() : Result::success($updated);
        } catch (Throwable $exception) {
            return Result::failure($this->normalizeFailure($exception, 'tenant.domain.set_primary'));
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
                    'status' => TenantDomainStatus::DISABLED,
                    'operational_status' => TenantDomainOperationalStatus::DISABLED,
                    'routing_status' => TenantDomainCheckStatus::PENDING,
                    'tls_status' => TenantDomainCheckStatus::PENDING,
                    'reachability_status' => TenantDomainCheckStatus::PENDING,
                    'operational_probe_token' => null,
                    'operational_probe_token_hash' => null,
                    'updated_by' => $this->currentUser->currentUserId(),
                ]);
                if ($updated === null) {
                    return null;
                }

                $this->recordAudit(
                    'tenant.domain.disabled',
                    $updated,
                    [
                        'status' => $record->get('status'),
                        'operational_status' => $record->get('operational_status'),
                    ],
                    [
                        'status' => TenantDomainStatus::DISABLED,
                        'operational_status' => TenantDomainOperationalStatus::DISABLED,
                    ],
                );

                return $updated;
            });

            return $updated === null ? $this->versionConflict() : Result::success($updated);
        } catch (Throwable $exception) {
            return Result::failure($this->normalizeFailure($exception, 'tenant.domain.disable'));
        }
    }

    public function delete(int $tenantId, int|string $id, int $expectedVersion): Result
    {
        try {
            $record = $this->domains->findByIdForTenant($id, $tenantId);
            if ($record === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant domain not found.'));
            }
            if ((bool) $record->get('is_primary') || $record->get('status') !== TenantDomainStatus::DISABLED) {
                return Result::failure(new Error(
                    TenantErrorCode::CONFLICT,
                    'Disable the non-primary domain before removing it.',
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

            return $deleted ? Result::success(true) : $this->versionConflict();
        } catch (Throwable $exception) {
            return Result::failure($this->normalizeFailure($exception, 'tenant.domain.delete'));
        }
    }

    private function verificationHost(string $domain): string
    {
        return (string) config('tenant.domains.verification_txt_prefix', '_autoerp-verification').'.'.$domain;
    }

    private function verificationValue(string $token): string
    {
        return (string) config('tenant.domains.verification_value_prefix', 'autoerp-verification=').$token;
    }

    private function asDateTime(mixed $value): ?DateTimeImmutable
    {
        if ($value instanceof DateTimeImmutable) {
            return $value;
        }
        if ($value instanceof \DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        $text = trim((string) $value);

        return $text === '' ? null : new DateTimeImmutable($text);
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());

        return in_array($sqlState, ['23000', '23505'], true);
    }

    private function versionConflict(): Result
    {
        return Result::failure(new Error(
            TenantErrorCode::VERSION_CONFLICT,
            'Domain changed since it was loaded. Refresh and try again.',
        ));
    }

    private function normalizeFailure(Throwable $exception, string $operation): Error
    {
        return $this->errors->normalize(
            $exception,
            TenantErrorCode::INVALID_VALUE,
            ['operation' => $operation],
        );
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

    private function optionalString(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }

}
