<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Platform;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class PlatformOperationalInfrastructureHealthService
{
    private const MAILER_LOG = 'log';
    private const MAILER_ARRAY = 'array';
    private const NON_PRODUCTION_MAILERS = [self::MAILER_LOG, self::MAILER_ARRAY];
    private const QUEUE_SYNC = 'sync';
    private const QUEUE_DATABASE = 'database';
    private const JOBS_TABLE = 'jobs';
    private const FAILED_JOBS_TABLE = 'failed_jobs';

    public function __construct(private readonly Migrator $migrator) {}

    /** @return array<string, mixed> */
    public function inspect(): array
    {
        $mailer = trim((string) config('mail.default', ''));
        $fromAddress = trim((string) config('mail.from.address', ''));
        $queueConnection = trim((string) config('queue.default', ''));
        $migrationStatus = $this->migrationStatus();

        $mailReady = $mailer !== ''
            && ! in_array($mailer, self::NON_PRODUCTION_MAILERS, true)
            && $fromAddress !== '';
        $queueReady = $queueConnection !== '' && $queueConnection !== self::QUEUE_SYNC;

        $pendingJobs = null;
        $failedJobs = null;
        if ($queueConnection === self::QUEUE_DATABASE && Schema::hasTable(self::JOBS_TABLE)) {
            $pendingJobs = DB::table(self::JOBS_TABLE)->count();
        }
        if (Schema::hasTable(self::FAILED_JOBS_TABLE)) {
            $failedJobs = DB::table(self::FAILED_JOBS_TABLE)->count();
        }

        return [
            'ready' => $mailReady && $queueReady && (bool) $migrationStatus['ready'],
            'mail' => [
                'ready' => $mailReady,
                'mailer' => $mailer === '' ? null : $mailer,
                'from_address_configured' => $fromAddress !== '',
                'external_transport' => $mailer !== '' && ! in_array($mailer, self::NON_PRODUCTION_MAILERS, true),
            ],
            'queue' => [
                'ready' => $queueReady,
                'connection' => $queueConnection === '' ? null : $queueConnection,
                'requires_worker' => $queueConnection !== self::QUEUE_SYNC,
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
            ],
            'migrations' => $migrationStatus,
        ];
    }

    /** @return array{ready: bool, pending_count: int|null, pending: list<string>, error?: string} */
    private function migrationStatus(): array
    {
        try {
            $paths = array_values(array_unique([
                database_path('migrations'),
                ...$this->migrator->paths(),
            ]));
            $files = $this->migrator->getMigrationFiles($paths);
            $ran = $this->migrator->getRepository()->getRan();
            $pending = array_values(array_diff(array_keys($files), $ran));
            sort($pending);

            return [
                'ready' => $pending === [],
                'pending_count' => count($pending),
                'pending' => $pending,
            ];
        } catch (Throwable $exception) {
            return [
                'ready' => false,
                'pending_count' => null,
                'pending' => [],
                'error' => $exception->getMessage(),
            ];
        }
    }
}
