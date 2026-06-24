<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Storage;

use Illuminate\Support\Str;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\FileStorageServiceInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Tenant\Models\TenantStorageCleanupJobModel;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class TenantStorageCleanupService
{
    private const STATUS_PENDING = 'pending';
    private const STATUS_PROCESSING = 'processing';
    private const STATUS_COMPLETED = 'completed';
    private const STATUS_DEAD = 'dead';

    public function __construct(
        private readonly TenantStorageCleanupJobModel $jobs,
        private readonly FileStorageServiceInterface $files,
        private readonly ClockInterface $clock,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Persist the cleanup intent. Call this inside the same database transaction that
     * removes or replaces the metadata referencing the file.
     */
    public function schedule(int $tenantId, string $disk, string $path, string $reason): void
    {
        $disk = trim($disk);
        $path = trim($path);
        if ($tenantId < 1 || $disk === '' || $path === '') {
            throw new RuntimeException('A tenant, storage disk and storage path are required for cleanup.');
        }

        $this->executionContext->runForTenant($tenantId, function () use ($tenantId, $disk, $path, $reason): void {
            $this->jobs->newQuery()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'storage_disk' => $disk,
                    'storage_path' => $path,
                ],
                [
                    'reason' => mb_substr(trim($reason), 0, 255),
                    'status' => self::STATUS_PENDING,
                    'attempts' => 0,
                    'last_error' => null,
                    'next_attempt_at' => $this->clock->now(),
                    'claim_token' => null,
                    'claimed_at' => null,
                    'completed_at' => null,
                ],
            );
        });
    }

    /** Try one already-persisted cleanup intent without weakening durability on failure. */
    public function processPath(int $tenantId, string $disk, string $path): bool
    {
        return $this->executionContext->runForTenant(
            $tenantId,
            function () use ($tenantId, $disk, $path): bool {
                $job = $this->jobs->newQuery()
                    ->where('tenant_id', $tenantId)
                    ->where('storage_disk', $disk)
                    ->where('storage_path', $path)
                    ->first();

                if (! $job instanceof TenantStorageCleanupJobModel) {
                    return false;
                }

                $claim = $this->claim((int) $job->getKey());

                return $claim === null || $this->processClaimed($claim);
            },
        );
    }

    /** @return array{checked:int,completed:int,failed:int,dead:int,recovered:int} */
    public function process(?int $limit = null): array
    {
        return $this->executionContext->runAsControlPlane(
            fn (): array => $this->processAcrossTenants($limit),
        );
    }

    /** @return array{checked:int,completed:int,failed:int,dead:int,recovered:int} */
    private function processAcrossTenants(?int $limit): array
    {
        $summary = ['checked' => 0, 'completed' => 0, 'failed' => 0, 'dead' => 0, 'recovered' => 0];
        $summary['recovered'] = $this->recoverStaleClaims();
        $batchSize = max(1, min($limit ?? (int) config('tenant.storage_cleanup.batch_size', 100), 500));

        $ids = $this->jobs->newQuery()
            ->where('status', self::STATUS_PENDING)
            ->where(function ($query): void {
                $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', $this->clock->now());
            })
            ->orderBy('next_attempt_at')
            ->orderBy('id')
            ->limit($batchSize)
            ->pluck('id')
            ->all();

        foreach ($ids as $id) {
            $claim = $this->claim((int) $id);
            if ($claim === null) {
                continue;
            }

            $summary['checked']++;
            $completed = $this->executionContext->runForTenant(
                (int) $claim->getAttribute('tenant_id'),
                fn (): bool => $this->processClaimed($claim),
            );

            if ($completed) {
                $summary['completed']++;
                continue;
            }

            $fresh = $this->jobs->newQuery()->find($claim->getKey());
            if ($fresh?->getAttribute('status') === self::STATUS_DEAD) {
                $summary['dead']++;
            } else {
                $summary['failed']++;
            }
        }

        return $summary;
    }

    private function claim(int $jobId): ?TenantStorageCleanupJobModel
    {
        $token = (string) Str::uuid();
        $updated = $this->jobs->newQuery()
            ->whereKey($jobId)
            ->where('status', self::STATUS_PENDING)
            ->where(function ($query): void {
                $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', $this->clock->now());
            })
            ->update([
                'status' => self::STATUS_PROCESSING,
                'claim_token' => $token,
                'claimed_at' => $this->clock->now(),
                'updated_at' => $this->clock->now(),
            ]);

        if ($updated !== 1) {
            return null;
        }

        return $this->jobs->newQuery()
            ->whereKey($jobId)
            ->where('claim_token', $token)
            ->first();
    }

    private function processClaimed(TenantStorageCleanupJobModel $job): bool
    {
        try {
            $disk = (string) $job->getAttribute('storage_disk');
            $path = (string) $job->getAttribute('storage_path');
            $deleted = ! $this->files->exists($path, $disk) || $this->files->delete($path, $disk);
            if (! $deleted) {
                throw new RuntimeException('Storage adapter returned false while deleting the file.');
            }

            $updated = $this->jobs->newQuery()
                ->whereKey($job->getKey())
                ->where('status', self::STATUS_PROCESSING)
                ->where('claim_token', $job->getAttribute('claim_token'))
                ->update([
                    'status' => self::STATUS_COMPLETED,
                    'completed_at' => $this->clock->now(),
                    'last_error' => null,
                    'next_attempt_at' => null,
                    'claim_token' => null,
                    'claimed_at' => null,
                    'updated_at' => $this->clock->now(),
                ]);

            return $updated === 1;
        } catch (Throwable $exception) {
            $attempts = ((int) $job->getAttribute('attempts')) + 1;
            $maxAttempts = max(1, (int) config('tenant.storage_cleanup.max_attempts', 10));
            $dead = $attempts >= $maxAttempts;
            $delayMinutes = min(60, 2 ** min($attempts, 6));

            $this->jobs->newQuery()
                ->whereKey($job->getKey())
                ->where('status', self::STATUS_PROCESSING)
                ->where('claim_token', $job->getAttribute('claim_token'))
                ->update([
                    'status' => $dead ? self::STATUS_DEAD : self::STATUS_PENDING,
                    'attempts' => $attempts,
                    'last_error' => mb_substr($exception->getMessage(), 0, 500),
                    'next_attempt_at' => $dead ? null : $this->clock->now()->modify("+{$delayMinutes} minutes"),
                    'claim_token' => null,
                    'claimed_at' => null,
                    'updated_at' => $this->clock->now(),
                ]);

            $this->logger->log($dead ? 'error' : 'warning', 'Tenant storage cleanup attempt failed.', [
                'cleanup_job_id' => $job->getKey(),
                'tenant_id' => $job->getAttribute('tenant_id'),
                'disk' => $job->getAttribute('storage_disk'),
                'path' => $job->getAttribute('storage_path'),
                'attempts' => $attempts,
                'dead' => $dead,
                'exception' => $exception,
            ]);

            return false;
        }
    }

    private function recoverStaleClaims(): int
    {
        $timeout = max(60, (int) config('tenant.storage_cleanup.claim_timeout_seconds', 900));
        $threshold = $this->clock->now()->modify("-{$timeout} seconds");

        return $this->jobs->newQuery()
            ->where('status', self::STATUS_PROCESSING)
            ->whereNotNull('claimed_at')
            ->where('claimed_at', '<=', $threshold)
            ->update([
                'status' => self::STATUS_PENDING,
                'claim_token' => null,
                'claimed_at' => null,
                'next_attempt_at' => $this->clock->now(),
                'updated_at' => $this->clock->now(),
            ]);
    }
}
