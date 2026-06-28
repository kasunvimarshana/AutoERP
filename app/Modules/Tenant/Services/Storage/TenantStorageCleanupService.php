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

    private const ERROR_DELETE_FAILED = 'TENANT_STORAGE_DELETE_FAILED';
    private const SAFE_DELETE_MESSAGE = 'The tenant file could not be removed from private storage.';

    public function __construct(
        private readonly TenantStorageCleanupJobModel $jobs,
        private readonly FileStorageServiceInterface $files,
        private readonly ClockInterface $clock,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly TenantStoragePathPolicy $paths,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Persist a cleanup intent inside the same transaction that removes or replaces
     * the database record. The configured private disk and tenant prefix are resolved
     * here and can never be supplied by callers.
     */
    public function schedule(int $tenantId, string $path, string $reason): void
    {
        $path = $this->paths->canonicalize($tenantId, $path);
        $reason = trim($reason);
        if ($reason === '') {
            throw new RuntimeException('A cleanup reason is required.');
        }

        $this->executionContext->runForTenant($tenantId, function () use ($tenantId, $path, $reason): void {
            $this->jobs->newQuery()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'storage_path' => $path,
                ],
                [
                    'reason' => mb_substr($reason, 0, 255),
                    'status' => self::STATUS_PENDING,
                    'attempts' => 0,
                    'last_error_code' => null,
                    'last_error_message' => null,
                    'next_attempt_at' => $this->clock->now(),
                    'claim_token' => null,
                    'claimed_at' => null,
                    'claim_lease_expires_at' => null,
                    'completed_at' => null,
                ],
            );
        });
    }

    /** Try one persisted cleanup intent without weakening durability on failure. */
    public function processPath(int $tenantId, string $path): bool
    {
        $path = $this->paths->canonicalize($tenantId, $path);

        return $this->executionContext->runForTenant(
            $tenantId,
            function () use ($tenantId, $path): bool {
                $job = $this->jobs->newQuery()
                    ->where('tenant_id', $tenantId)
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

    public function retryDead(?int $jobId = null, ?int $tenantId = null, ?int $limit = null): int
    {
        return $this->executionContext->runAsControlPlane(function () use ($jobId, $tenantId, $limit): int {
            $query = $this->jobs->newQuery()->where('status', self::STATUS_DEAD);
            if ($jobId !== null) {
                $query->whereKey($jobId);
            }
            if ($tenantId !== null) {
                $query->where('tenant_id', $tenantId);
            }
            if ($jobId === null) {
                $query->limit(max(1, min($limit ?? 100, 500)));
            }

            $ids = $query->pluck('id')->all();
            if ($ids === []) {
                return 0;
            }

            return $this->jobs->newQuery()
                ->whereIn('id', $ids)
                ->where('status', self::STATUS_DEAD)
                ->update([
                    'status' => self::STATUS_PENDING,
                    'attempts' => 0,
                    'last_error_code' => null,
                    'last_error_message' => null,
                    'next_attempt_at' => $this->clock->now(),
                    'claim_token' => null,
                    'claimed_at' => null,
                    'claim_lease_expires_at' => null,
                    'completed_at' => null,
                    'updated_at' => $this->clock->now(),
                ]);
        });
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
            $summary[$fresh?->getAttribute('status') === self::STATUS_DEAD ? 'dead' : 'failed']++;
        }

        return $summary;
    }

    private function claim(int $jobId): ?TenantStorageCleanupJobModel
    {
        $token = (string) Str::uuid();
        $timeout = max(60, (int) config('tenant.storage_cleanup.claim_timeout_seconds', 900));
        $now = $this->clock->now();
        $updated = $this->jobs->newQuery()
            ->whereKey($jobId)
            ->where('status', self::STATUS_PENDING)
            ->where(function ($query) use ($now): void {
                $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', $now);
            })
            ->update([
                'status' => self::STATUS_PROCESSING,
                'claim_token' => $token,
                'claimed_at' => $now,
                'claim_lease_expires_at' => $now->modify("+{$timeout} seconds"),
                'updated_at' => $now,
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
            $tenantId = (int) $job->getAttribute('tenant_id');
            $path = $this->paths->canonicalize($tenantId, (string) $job->getAttribute('storage_path'));
            $disk = $this->disk();
            $deleted = ! $this->files->exists($path, $disk) || $this->files->delete($path, $disk);
            if (! $deleted) {
                throw new RuntimeException('Storage adapter returned false while deleting a tenant file.');
            }

            $updated = $this->jobs->newQuery()
                ->whereKey($job->getKey())
                ->where('status', self::STATUS_PROCESSING)
                ->where('claim_token', $job->getAttribute('claim_token'))
                ->update([
                    'status' => self::STATUS_COMPLETED,
                    'completed_at' => $this->clock->now(),
                    'last_error_code' => null,
                    'last_error_message' => null,
                    'next_attempt_at' => null,
                    'claim_token' => null,
                    'claimed_at' => null,
                    'claim_lease_expires_at' => null,
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
                    'last_error_code' => self::ERROR_DELETE_FAILED,
                    'last_error_message' => self::SAFE_DELETE_MESSAGE,
                    'next_attempt_at' => $dead ? null : $this->clock->now()->modify("+{$delayMinutes} minutes"),
                    'claim_token' => null,
                    'claimed_at' => null,
                    'claim_lease_expires_at' => null,
                    'updated_at' => $this->clock->now(),
                ]);

            $this->logger->log($dead ? 'error' : 'warning', 'Tenant storage cleanup attempt failed.', [
                'cleanup_job_id' => $job->getKey(),
                'tenant_id' => $job->getAttribute('tenant_id'),
                'storage_path' => $job->getAttribute('storage_path'),
                'attempts' => $attempts,
                'dead' => $dead,
                'exception' => $exception,
            ]);

            return false;
        }
    }

    private function recoverStaleClaims(): int
    {
        $now = $this->clock->now();

        return $this->jobs->newQuery()
            ->where('status', self::STATUS_PROCESSING)
            ->whereNotNull('claim_lease_expires_at')
            ->where('claim_lease_expires_at', '<=', $now)
            ->update([
                'status' => self::STATUS_PENDING,
                'claim_token' => null,
                'claimed_at' => null,
                'claim_lease_expires_at' => null,
                'next_attempt_at' => $now,
                'updated_at' => $now,
            ]);
    }

    private function disk(): string
    {
        $disk = trim((string) config('tenant.documents.disk', 'tenant_private'));
        if ($disk === '') {
            throw new RuntimeException('The tenant private storage disk is not configured.');
        }

        return $disk;
    }
}
