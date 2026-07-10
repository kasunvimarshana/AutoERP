<?php

declare(strict_types=1);

namespace Modules\Tenant\Console\Commands;

use Illuminate\Console\Command;
use Modules\Tenant\Services\Platform\PlatformOperationalInfrastructureHealthService;

final class PlatformHealthCommand extends Command
{
    private const STATUS_READY = 'READY';
    private const STATUS_FAILED = 'FAILED';
    private const CHECK_MAIL = 'mail';
    private const CHECK_QUEUE = 'queue';
    private const CHECK_MIGRATIONS = 'migrations';

    protected $signature = 'platform:health';

    protected $description = 'Verify platform operational health checks for production readiness.';

    public function handle(PlatformOperationalInfrastructureHealthService $health): int
    {
        $result = $health->inspect();

        $this->table(
            ['Check', 'Status', 'Message'],
            [
                [self::CHECK_MAIL, $this->status((bool) $result['mail']['ready']), $this->mailMessage($result['mail'])],
                [self::CHECK_QUEUE, $this->status((bool) $result['queue']['ready']), $this->queueMessage($result['queue'])],
                [self::CHECK_MIGRATIONS, $this->status((bool) $result['migrations']['ready']), $this->migrationMessage($result['migrations'])],
            ],
        );

        if (! (bool) $result['ready']) {
            $this->error('Platform operational health failed. Do not route production traffic to this deployment.');

            return self::FAILURE;
        }

        $this->info('Platform operational health passed.');

        return self::SUCCESS;
    }

    /** @param array<string,mixed> $mail */
    private function mailMessage(array $mail): string
    {
        return sprintf(
            'mailer=%s, from_address_configured=%s, external_transport=%s',
            $this->display($mail['mailer'] ?? null),
            $this->boolean($mail['from_address_configured'] ?? false),
            $this->boolean($mail['external_transport'] ?? false),
        );
    }

    /** @param array<string,mixed> $queue */
    private function queueMessage(array $queue): string
    {
        return sprintf(
            'connection=%s, requires_worker=%s, pending_jobs=%s, failed_jobs=%s',
            $this->display($queue['connection'] ?? null),
            $this->boolean($queue['requires_worker'] ?? false),
            $this->display($queue['pending_jobs'] ?? null),
            $this->display($queue['failed_jobs'] ?? null),
        );
    }

    /** @param array<string,mixed> $migrations */
    private function migrationMessage(array $migrations): string
    {
        $pendingCount = $migrations['pending_count'] ?? null;
        $error = $migrations['error'] ?? null;

        if (is_string($error) && $error !== '') {
            return 'migration_status_error='.$error;
        }

        return sprintf('pending_count=%s', $this->display($pendingCount));
    }

    private function status(bool $ready): string
    {
        return $ready ? self::STATUS_READY : self::STATUS_FAILED;
    }

    private function boolean(mixed $value): string
    {
        return (bool) $value ? 'yes' : 'no';
    }

    private function display(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'none';
        }

        if (is_bool($value)) {
            return $this->boolean($value);
        }

        return (string) $value;
    }
}
