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
        $invitationUrl = trim((string) config('module-auth.registration.invitation_url', ''));
        $queueConnection = trim((string) config('queue.default', ''));
        $urlScheme = strtolower((string) parse_url($invitationUrl, PHP_URL_SCHEME));

        $invitationUrlReady = filter_var($invitationUrl, FILTER_VALIDATE_URL) !== false
            && in_array($urlScheme, ['http', 'https'], true);
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
            'ready' => $mailReady && $queueReady && $invitationUrlReady,
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
            'administrator_invitation_url' => [
                'ready' => $invitationUrlReady,
                'origin' => $invitationUrlReady
                    ? parse_url($invitationUrl, PHP_URL_SCHEME).'://'.parse_url($invitationUrl, PHP_URL_HOST)
                    : null,
            ],
        ];
    }
}
