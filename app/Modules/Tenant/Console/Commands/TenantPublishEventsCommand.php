<?php

declare(strict_types=1);

namespace Modules\Tenant\Console\Commands;

use Illuminate\Console\Command;
use Modules\Tenant\Services\Events\TenantEventOutboxService;

final class TenantPublishEventsCommand extends Command
{
    protected $signature = 'tenant:events:publish {--limit= : Maximum outbox events to publish}';

    protected $description = 'Publish pending tenant lifecycle events from the durable outbox.';

    public function handle(TenantEventOutboxService $service): int
    {
        $limit = $this->option('limit');
        $summary = $service->publish(is_numeric($limit) ? (int) $limit : null);

        $this->info(sprintf(
            'Checked %d; published %d; failed %d.',
            $summary['checked'],
            $summary['published'],
            $summary['failed'],
        ));

        return $summary['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
