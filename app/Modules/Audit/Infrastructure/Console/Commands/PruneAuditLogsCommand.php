<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Console\Commands;

use Illuminate\Console\Command;
use Carbon\CarbonImmutable;
use Modules\Audit\Application\Contracts\AuditServiceInterface;

class PruneAuditLogsCommand extends Command
{
    protected $signature = 'audit:prune
        {--months=12 : Delete logs older than this number of months}';

    protected $description = 'Permanently delete old audit logs.';

    public function __construct(private readonly AuditServiceInterface $auditService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $months = (int) $this->option('months');

        if ($months <= 0) {
            $this->error('The --months option must be greater than zero.');

            return self::INVALID;
        }

        $before = CarbonImmutable::now()->subMonths($months);
        $deleted = $this->auditService->pruneOlderThan($before);

        $this->info(sprintf(
            'Deleted %d audit log(s) older than %s.',
            $deleted,
            $before->toDateTimeString()
        ));

        return self::SUCCESS;
    }
}
