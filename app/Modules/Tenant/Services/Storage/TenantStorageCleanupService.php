<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Storage;

use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\FileStorageServiceInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Tenant\Models\TenantStorageCleanupJobModel;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

final class TenantStorageCleanupService
{
    public function __construct(
        private readonly TenantStorageCleanupJobModel $jobs,
        private readonly FileStorageServiceInterface $files,
        private readonly ClockInterface $clock,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly LoggerInterface $logger,
    ) {}

    public function enqueue(int $tenantId, string $disk, string $path, string $reason): bool
    {
        try {
            return $this->executionContext->runForTenant(
                $tenantId,
                function () use ($tenantId, $disk, $path, $reason): bool {
                    $this->jobs->newQuery()->updateOrCreate(
                        [
                            'tenant_id' => $tenantId,
                            'storage_disk' => $disk,
                            'storage_path' => $path,
                        ],
                        [
                            'reason' => mb_substr(trim($reason), 0, 255),
                            'status' => 'pending',
                            'last_error' => null,
                            'next_attempt_at' => $this->clock->now(),
                            'completed_at' => null,
                        ],
                    );

                    return true;
                },
            );
        } catch (Throwable $exception) {
            $this->logger->critical('Tenant storage cleanup could not be queued.', [
                'tenant_id' => $tenantId,
                'disk' => $disk,
                'path' => $path,
                'exception' => $exception,
            ]);

            return false;
        }
    }

    /** @return array{checked:int,completed:int,failed:int} */
    public function process(?int $limit = null): array
    {
        return $this->executionContext->runAsControlPlane(
            fn (): array => $this->processAcrossTenants($limit),
        );
    }

    /** @return array{checked:int,completed:int,failed:int} */
    private function processAcrossTenants(?int $limit): array
    {
        $now = $this->clock->now();
        $batchSize = max(1, min($limit ?? 100, 500));
        $summary = ['checked' => 0, 'completed' => 0, 'failed' => 0];

        $jobs = $this->jobs->newQuery()
            ->where('status', 'pending')
            ->where(function ($query) use ($now): void {
                $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', $now);
            })
            ->orderBy('next_attempt_at')
            ->orderBy('id')
            ->limit($batchSize)
            ->get();

        foreach ($jobs as $job) {
            $summary['checked']++;
            $this->executionContext->runForTenant(
                (int) $job->tenant_id,
                function () use ($job, $now, &$summary): void {
                    try {
                        $disk = (string) $job->storage_disk;
                        $path = (string) $job->storage_path;
                        $deleted = ! $this->files->exists($path, $disk)
                            || $this->files->delete($path, $disk);

                        if (! $deleted) {
                            throw new RuntimeException('Storage adapter returned false while deleting the file.');
                        }

                        $job->forceFill([
                            'status' => 'completed',
                            'completed_at' => $now,
                            'last_error' => null,
                            'next_attempt_at' => null,
                        ])->save();
                        $summary['completed']++;
                    } catch (Throwable $exception) {
                        $attempts = ((int) $job->attempts) + 1;
                        $delayMinutes = min(60, 2 ** min($attempts, 6));
                        $job->forceFill([
                            'attempts' => $attempts,
                            'last_error' => mb_substr($exception->getMessage(), 0, 500),
                            'next_attempt_at' => $now->modify("+{$delayMinutes} minutes"),
                        ])->save();
                        $summary['failed']++;

                        $this->logger->warning('Tenant storage cleanup attempt failed.', [
                            'cleanup_job_id' => $job->getKey(),
                            'tenant_id' => $job->tenant_id,
                            'disk' => $job->storage_disk,
                            'path' => $job->storage_path,
                            'attempts' => $attempts,
                            'exception' => $exception,
                        ]);
                    }
                },
            );
        }

        return $summary;
    }
}
