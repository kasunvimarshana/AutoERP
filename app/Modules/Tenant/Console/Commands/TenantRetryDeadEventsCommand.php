<?php

declare(strict_types=1);

namespace Modules\Tenant\Console\Commands;

use Illuminate\Console\Command;
use Modules\Tenant\Services\Events\TenantEventOutboxService;

final class TenantRetryDeadEventsCommand extends Command
{
    protected $signature = 'tenant:events:retry-dead {event_uuid? : Retry one dead event} {--limit=100 : Maximum events when no UUID is provided}';

    protected $description = 'Move selected dead tenant outbox events back to the pending queue.';

    public function handle(TenantEventOutboxService $service): int
    {
        $eventUuid = $this->argument('event_uuid');
        $limit = $this->option('limit');
        $retried = $service->retryDead(
            is_string($eventUuid) && trim($eventUuid) !== '' ? trim($eventUuid) : null,
            is_numeric($limit) ? (int) $limit : 100,
        );

        $this->info("Queued {$retried} dead tenant event(s) for retry.");

        return self::SUCCESS;
    }
}
