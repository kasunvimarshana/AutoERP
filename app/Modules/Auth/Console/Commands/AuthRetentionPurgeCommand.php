<?php

declare(strict_types=1);

namespace Modules\Auth\Console\Commands;

use Illuminate\Console\Command;
use Modules\Auth\Services\AuthRetentionService;

final class AuthRetentionPurgeCommand extends Command
{
    protected $signature = 'auth:retention:purge';
    protected $description = 'Purge expired Auth operational records according to configured retention periods.';

    public function handle(AuthRetentionService $retention): int
    {
        $counts = $retention->purge();
        foreach ($counts as $recordType => $count) {
            $this->line(sprintf('%s: %d', $recordType, $count));
        }
        return self::SUCCESS;
    }
}
