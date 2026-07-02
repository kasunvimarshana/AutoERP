<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Platform;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PlatformOperationalInfrastructureHealthService
{
    /** @return array<string, mixed> */
    public function inspect(): array
    {
        $mailer = trim((string) config('mail.default', ''));
        $fromAddress = trim((string) config('mail.from.address', ''));
        $queueConnection = trim((string) config('queue.default', ''));

        $mailReady = $mailer !== ''
            && ! in_array($mailer, ['log', 'array'], true)
            && $fromAddress !== '';
        $queueReady = $queueConnection !== '' && $queueConnection !== 'sync';

        $pendingJobs = null;
        $failedJobs = null;
        if ($queueConnection === 'database' && Schema::hasTable('jobs')) {
            $pendingJobs = DB::table('jobs')->count();
        }
        if (Schema::hasTable('failed_jobs')) {
            $failedJobs = DB::table('failed_jobs')->count();
        }

        return [
            'ready' => $mailReady && $queueReady,
            'mail' => [
                'ready' => $mailReady,
                'mailer' => $mailer === '' ? null : $mailer,
                'from_address_configured' => $fromAddress !== '',
                'external_transport' => ! in_array($mailer, ['', 'log', 'array'], true),
            ],
            'queue' => [
                'ready' => $queueReady,
                'connection' => $queueConnection === '' ? null : $queueConnection,
                'requires_worker' => $queueConnection !== 'sync',
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
            ],
        ];
    }
}
