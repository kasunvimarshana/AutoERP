<?php

declare(strict_types=1);

namespace Modules\Tenant\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Tenant\Models\TenantDomainModel;
use RuntimeException;

final class EloquentTenantDomainRepository implements TenantDomainRepositoryInterface
{
    private const VERSION_CONFLICT = 'tenant-domain-version-conflict';

    public function __construct(
        private readonly TenantDomainModel $model,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {}

    public function listByTenant(int $tenantId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('is_primary')
            ->orderBy('domain')
            ->get()
            ->map(fn (Model $model): DataRecord => $this->record($model))
            ->values()
            ->all();
    }

    public function findByIdForTenant(int|string $id, int $tenantId): ?DataRecord
    {
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model instanceof TenantDomainModel ? $this->record($model) : null;
    }

    public function findByDomain(string $domain): ?DataRecord
    {
        return $this->executionContext->runAsControlPlane(function () use ($domain): ?DataRecord {
            $model = $this->model->newQuery()
                ->where('domain', strtolower(trim($domain)))
                ->first();

            return $model instanceof TenantDomainModel ? $this->record($model) : null;
        });
    }

    public function findPrimaryByTenant(int $tenantId): ?DataRecord
    {
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('is_primary', true)
            ->where('status', 'active')
            ->whereNotNull('verified_at')
            ->first();

        return $model instanceof TenantDomainModel ? $this->record($model) : null;
    }

    public function create(array $attributes): DataRecord
    {
        return $this->record($this->model->newQuery()->create($attributes));
    }

    public function updateWithVersion(
        int|string $id,
        int $tenantId,
        int $expectedVersion,
        array $attributes,
    ): ?DataRecord {
        $attributes['row_version'] = $expectedVersion + 1;
        $attributes['updated_at'] = now();

        $updated = $this->model->newQuery()
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
                $target = $this->model->newQuery()
                    ->whereKey($id)
                    ->where('tenant_id', $tenantId)
                    ->where('row_version', $expectedVersion)
                    ->lockForUpdate()
                    ->first();

                if (! $target instanceof TenantDomainModel) {
                    throw new RuntimeException(self::VERSION_CONFLICT);
                }

                $this->model->newQuery()
                    ->where('tenant_id', $tenantId)
                    ->where('is_primary', true)
                    ->where('id', '!=', $target->getKey())
                    ->update([
                        'is_primary' => false,
                        'primary_marker' => null,
                        'row_version' => DB::raw('row_version + 1'),
                        'updated_by' => $updatedBy,
                        'updated_at' => now(),
                    ]);

                $updated = $this->model->newQuery()
                    ->whereKey($id)
                    ->where('tenant_id', $tenantId)
                    ->where('row_version', $expectedVersion)
                    ->update([
                        'is_primary' => true,
                        'primary_marker' => 'primary',
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
        return $this->model->newQuery()
            ->whereKey($id)
            ->where('tenant_id', $tenantId)
            ->where('row_version', $expectedVersion)
            ->delete() === 1;
    }

    public function recordVerificationAttempt(
        int|string $id,
        int $tenantId,
        bool $verified,
        ?string $error,
        \DateTimeInterface $attemptedAt,
        ?\DateTimeInterface $revalidationDueAt = null,
        ?\DateTimeInterface $graceExpiresAt = null,
    ): void {
        $attributes = [
            'last_verification_attempt_at' => $attemptedAt,
            'verification_last_error' => $verified ? null : mb_substr(trim((string) $error), 0, 500),
            'updated_at' => $attemptedAt,
        ];

        if ($verified) {
            $attributes['last_verified_at'] = $attemptedAt;
            $attributes['verification_failure_count'] = 0;
            $attributes['revalidation_due_at'] = $revalidationDueAt;
            $attributes['verification_grace_expires_at'] = $graceExpiresAt;
        } else {
            $attributes['verification_failure_count'] = \Illuminate\Support\Facades\DB::raw(
                'verification_failure_count + 1',
            );
        }

        $this->model->newQuery()
            ->whereKey($id)
            ->where('tenant_id', $tenantId)
            ->update($attributes);
    }

    public function listDueForRevalidation(\DateTimeInterface $dueAt, int $limit): array
    {
        return $this->executionContext->runAsControlPlane(fn (): array => $this->model->newQuery()
            ->where('status', 'active')
            ->whereNotNull('verified_token_hash')
            ->whereNotNull('revalidation_due_at')
            ->where('revalidation_due_at', '<=', $dueAt)
            ->orderBy('revalidation_due_at')
            ->limit(max(1, min($limit, 500)))
            ->get()
            ->map(fn (Model $model): DataRecord => $this->record($model))
            ->values()
            ->all());
    }

    private function record(Model $model): DataRecord
    {
        return new DataRecord($model->attributesToArray());
    }
}
