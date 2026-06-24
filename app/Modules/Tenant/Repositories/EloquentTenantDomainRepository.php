<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Tenant\Models\TenantDomainModel;
use Modules\Tenant\Models\TenantPrimaryDomainModel;
use RuntimeException;

final class EloquentTenantDomainRepository implements TenantDomainRepositoryInterface
{
    private const VERSION_CONFLICT = 'tenant-domain-version-conflict';

    public function __construct(
        private readonly TenantDomainModel $domains,
        private readonly TenantPrimaryDomainModel $primaryDomains,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {}

    public function listByTenant(int $tenantId): array
    {
        return $this->domainQuery()
            ->where('tenant_domains.tenant_id', $tenantId)
            ->orderByDesc('is_primary')
            ->orderBy('tenant_domains.domain')
            ->get()
            ->map(fn (Model $model): DataRecord => $this->record($model))
            ->values()
            ->all();
    }

    public function findByIdForTenant(int|string $id, int $tenantId): ?DataRecord
    {
        $model = $this->domainQuery()
            ->where('tenant_domains.tenant_id', $tenantId)
            ->where('tenant_domains.id', $id)
            ->first();

        return $model instanceof TenantDomainModel ? $this->record($model) : null;
    }

    public function findByDomainFromControlPlane(string $domain): ?DataRecord
    {
        return $this->executionContext->runAsControlPlane(function () use ($domain): ?DataRecord {
            $model = $this->domainQuery()
                ->where('tenant_domains.domain', strtolower(trim($domain)))
                ->first();

            return $model instanceof TenantDomainModel ? $this->record($model) : null;
        });
    }

    public function findPrimaryByTenant(int $tenantId): ?DataRecord
    {
        $model = $this->domainQuery()
            ->where('tenant_domains.tenant_id', $tenantId)
            ->whereColumn('tenant_primary_domains.tenant_domain_id', 'tenant_domains.id')
            ->where('tenant_domains.status', 'active')
            ->whereNotNull('tenant_domains.verified_at')
            ->first();

        return $model instanceof TenantDomainModel ? $this->record($model) : null;
    }

    public function create(array $attributes): DataRecord
    {
        $model = $this->domains->newQuery()->create($attributes);

        return $this->findByIdForTenant($model->getKey(), (int) $model->getAttribute('tenant_id'))
            ?? $this->record($model, false);
    }

    public function updateWithVersion(
        int|string $id,
        int $tenantId,
        int $expectedVersion,
        array $attributes,
    ): ?DataRecord {
        unset($attributes['tenant_id'], $attributes['id'], $attributes['is_primary']);
        $attributes['row_version'] = $expectedVersion + 1;
        $attributes['updated_at'] = now();

        $updated = $this->domains->newQuery()
            ->whereKey($id)
            ->where('tenant_id', $tenantId)
            ->where('row_version', $expectedVersion)
            ->update($attributes);

        return $updated === 1 ? $this->findByIdForTenant($id, $tenantId) : null;
    }

    public function setPrimaryWithVersion(
        int|string $id,
        int $tenantId,
        int $expectedVersion,
        ?int $updatedBy,
    ): ?DataRecord {
        try {
            DB::transaction(function () use ($id, $tenantId, $expectedVersion, $updatedBy): void {
                $target = $this->domains->newQuery()
                    ->whereKey($id)
                    ->where('tenant_id', $tenantId)
                    ->where('row_version', $expectedVersion)
                    ->where('status', 'active')
                    ->whereNotNull('verified_at')
                    ->lockForUpdate()
                    ->first();

                if (! $target instanceof TenantDomainModel) {
                    throw new RuntimeException(self::VERSION_CONFLICT);
                }

                $assignment = $this->primaryDomains->newQuery()
                    ->where('tenant_id', $tenantId)
                    ->lockForUpdate()
                    ->first();
                $previousDomainId = $assignment?->getAttribute('tenant_domain_id');

                if (is_numeric($previousDomainId) && (int) $previousDomainId !== (int) $target->getKey()) {
                    $this->domains->newQuery()
                        ->whereKey((int) $previousDomainId)
                        ->where('tenant_id', $tenantId)
                        ->update([
                            'row_version' => DB::raw('row_version + 1'),
                            'updated_by' => $updatedBy,
                            'updated_at' => now(),
                        ]);
                }

                if ($assignment instanceof TenantPrimaryDomainModel) {
                    $assignment->forceFill([
                        'tenant_domain_id' => (int) $target->getKey(),
                        'row_version' => (int) $assignment->getAttribute('row_version') + 1,
                        'updated_by' => $updatedBy,
                    ])->save();
                } else {
                    $this->primaryDomains->newQuery()->create([
                        'tenant_id' => $tenantId,
                        'tenant_domain_id' => (int) $target->getKey(),
                        'row_version' => 1,
                        'updated_by' => $updatedBy,
                    ]);
                }

                $updated = $this->domains->newQuery()
                    ->whereKey($id)
                    ->where('tenant_id', $tenantId)
                    ->where('row_version', $expectedVersion)
                    ->update([
                        'row_version' => $expectedVersion + 1,
                        'updated_by' => $updatedBy,
                        'updated_at' => now(),
                    ]);

                if ($updated !== 1) {
                    throw new RuntimeException(self::VERSION_CONFLICT);
                }
            }, 3);
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === self::VERSION_CONFLICT) {
                return null;
            }

            throw $exception;
        }

        return $this->findByIdForTenant($id, $tenantId);
    }

    public function deleteWithVersion(int|string $id, int $tenantId, int $expectedVersion): bool
    {
        return $this->domains->newQuery()
            ->whereKey($id)
            ->where('tenant_id', $tenantId)
            ->where('row_version', $expectedVersion)
            ->delete() === 1;
    }

    public function recordVerificationAttempt(
        int|string $id,
        int $tenantId,
        int $expectedVersion,
        bool $verified,
        ?string $error,
        DateTimeInterface $attemptedAt,
        ?DateTimeInterface $revalidationDueAt = null,
        ?DateTimeInterface $graceExpiresAt = null,
    ): ?DataRecord {
        $attributes = [
            'last_verification_attempt_at' => $attemptedAt,
            'verification_last_error' => $verified ? null : mb_substr(trim((string) $error), 0, 500),
            'revalidation_claim_token' => null,
            'revalidation_claimed_at' => null,
            'row_version' => $expectedVersion + 1,
            'updated_at' => $attemptedAt,
        ];

        if ($verified) {
            $attributes['last_verified_at'] = $attemptedAt;
            $attributes['verification_failure_count'] = 0;
            $attributes['revalidation_due_at'] = $revalidationDueAt;
            $attributes['verification_grace_expires_at'] = $graceExpiresAt;
        } else {
            $attributes['verification_failure_count'] = DB::raw('verification_failure_count + 1');
        }

        $updated = $this->domains->newQuery()
            ->whereKey($id)
            ->where('tenant_id', $tenantId)
            ->where('row_version', $expectedVersion)
            ->update($attributes);

        return $updated === 1 ? $this->findByIdForTenant($id, $tenantId) : null;
    }

    public function claimDueForRevalidation(
        DateTimeInterface $dueAt,
        DateTimeInterface $claimedAt,
        DateTimeInterface $staleBefore,
        string $claimToken,
        int $limit,
    ): array {
        return $this->executionContext->runAsControlPlane(function () use (
            $dueAt,
            $claimedAt,
            $staleBefore,
            $claimToken,
            $limit,
        ): array {
            return DB::transaction(function () use (
                $dueAt,
                $claimedAt,
                $staleBefore,
                $claimToken,
                $limit,
            ): array {
                $records = $this->domains->newQuery()
                    ->where('status', 'active')
                    ->whereNotNull('verified_token_hash')
                    ->whereNotNull('revalidation_due_at')
                    ->where('revalidation_due_at', '<=', $dueAt)
                    ->where(function (Builder $query) use ($staleBefore): void {
                        $query->whereNull('revalidation_claimed_at')
                            ->orWhere('revalidation_claimed_at', '<=', $staleBefore);
                    })
                    ->orderBy('revalidation_due_at')
                    ->limit(max(1, min($limit, 500)))
                    ->lockForUpdate()
                    ->get();

                foreach ($records as $record) {
                    $version = (int) $record->getAttribute('row_version');
                    $this->domains->newQuery()
                        ->whereKey($record->getKey())
                        ->where('row_version', $version)
                        ->update([
                            'revalidation_claim_token' => $claimToken,
                            'revalidation_claimed_at' => $claimedAt,
                            'row_version' => $version + 1,
                            'updated_at' => $claimedAt,
                        ]);
                }

                return $this->domainQuery()
                    ->where('tenant_domains.revalidation_claim_token', $claimToken)
                    ->orderBy('tenant_domains.revalidation_due_at')
                    ->get()
                    ->map(fn (Model $model): DataRecord => $this->record($model))
                    ->values()
                    ->all();
            }, 3);
        });
    }

    public function releaseRevalidationClaim(
        int|string $id,
        int $tenantId,
        int $expectedVersion,
        string $claimToken,
        ?string $error,
        DateTimeInterface $releasedAt,
    ): ?DataRecord {
        $updated = $this->domains->newQuery()
            ->whereKey($id)
            ->where('tenant_id', $tenantId)
            ->where('row_version', $expectedVersion)
            ->where('revalidation_claim_token', $claimToken)
            ->update([
                'revalidation_claim_token' => null,
                'revalidation_claimed_at' => null,
                'verification_last_error' => mb_substr(trim((string) $error), 0, 500),
                'row_version' => $expectedVersion + 1,
                'updated_at' => $releasedAt,
            ]);

        return $updated === 1 ? $this->findByIdForTenant($id, $tenantId) : null;
    }

    public function disableAfterFailedRevalidation(
        int|string $id,
        int $tenantId,
        int $expectedVersion,
        string $claimToken,
        ?string $error,
        DateTimeInterface $attemptedAt,
        ?int $updatedBy,
    ): ?array {
        try {
            return DB::transaction(function () use (
                $id,
                $tenantId,
                $expectedVersion,
                $claimToken,
                $error,
                $attemptedAt,
                $updatedBy,
            ): array {
                $target = $this->domains->newQuery()
                    ->whereKey($id)
                    ->where('tenant_id', $tenantId)
                    ->where('row_version', $expectedVersion)
                    ->where('revalidation_claim_token', $claimToken)
                    ->lockForUpdate()
                    ->first();

                if (! $target instanceof TenantDomainModel) {
                    throw new RuntimeException(self::VERSION_CONFLICT);
                }

                $assignment = $this->primaryDomains->newQuery()
                    ->where('tenant_id', $tenantId)
                    ->lockForUpdate()
                    ->first();
                $wasPrimary = $assignment instanceof TenantPrimaryDomainModel
                    && (int) $assignment->getAttribute('tenant_domain_id') === (int) $target->getKey();

                $updated = $this->domains->newQuery()
                    ->whereKey($id)
                    ->where('tenant_id', $tenantId)
                    ->where('row_version', $expectedVersion)
                    ->where('revalidation_claim_token', $claimToken)
                    ->update([
                        'status' => 'disabled',
                        'last_verification_attempt_at' => $attemptedAt,
                        'verification_failure_count' => DB::raw('verification_failure_count + 1'),
                        'verification_last_error' => mb_substr(trim((string) $error), 0, 500),
                        'revalidation_claim_token' => null,
                        'revalidation_claimed_at' => null,
                        'row_version' => $expectedVersion + 1,
                        'updated_by' => $updatedBy,
                        'updated_at' => $attemptedAt,
                    ]);

                if ($updated !== 1) {
                    throw new RuntimeException(self::VERSION_CONFLICT);
                }

                $fallback = null;
                if ($wasPrimary) {
                    $fallbackModel = $this->domains->newQuery()
                        ->where('tenant_id', $tenantId)
                        ->where('id', '!=', $target->getKey())
                        ->where('status', 'active')
                        ->whereNotNull('verified_at')
                        ->orderByDesc('last_verified_at')
                        ->orderBy('domain')
                        ->lockForUpdate()
                        ->first();

                    if ($fallbackModel instanceof TenantDomainModel) {
                        $assignment->forceFill([
                            'tenant_domain_id' => (int) $fallbackModel->getKey(),
                            'row_version' => (int) $assignment->getAttribute('row_version') + 1,
                            'updated_by' => $updatedBy,
                        ])->save();
                        $fallbackModel->forceFill([
                            'row_version' => (int) $fallbackModel->getAttribute('row_version') + 1,
                            'updated_by' => $updatedBy,
                        ])->save();
                        $fallback = $this->findByIdForTenant($fallbackModel->getKey(), $tenantId);
                    } else {
                        $assignment->delete();
                    }
                }

                $domain = $this->findByIdForTenant($id, $tenantId);
                if (! $domain instanceof DataRecord) {
                    throw new RuntimeException(self::VERSION_CONFLICT);
                }

                return [
                    'domain' => $domain,
                    'fallback_primary' => $fallback,
                    'primary_lost' => $wasPrimary && $fallback === null,
                ];
            }, 3);
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === self::VERSION_CONFLICT) {
                return null;
            }

            throw $exception;
        }
    }

    private function domainQuery(): Builder
    {
        return $this->domains->newQuery()
            ->leftJoin(
                'tenant_primary_domains',
                'tenant_primary_domains.tenant_domain_id',
                '=',
                'tenant_domains.id',
            )
            ->select('tenant_domains.*')
            ->selectRaw(
                'CASE WHEN tenant_primary_domains.tenant_domain_id IS NULL THEN 0 ELSE 1 END AS is_primary',
            );
    }

    private function record(Model $model, ?bool $isPrimary = null): DataRecord
    {
        $payload = $model->attributesToArray();
        $payload['is_primary'] = $isPrimary ?? (bool) ($model->getAttribute('is_primary') ?? false);

        return new DataRecord($payload);
    }
}
