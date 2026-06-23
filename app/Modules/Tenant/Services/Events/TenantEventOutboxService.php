<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Events;

use Illuminate\Contracts\Events\Dispatcher;
use Modules\Core\Contracts\ClockInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\Contracts\UuidGeneratorInterface;
use Modules\Tenant\Events\TenantStatusChanged;
use Modules\Tenant\Models\TenantEventOutboxModel;
use Psr\Log\LoggerInterface;
use Throwable;

final class TenantEventOutboxService
{
    private const STATUS_CHANGED = 'tenant.status_changed';
    private const CLAIM_TIMEOUT_MINUTES = 10;

    public function __construct(
        private readonly TenantEventOutboxModel $outbox,
        private readonly UuidGeneratorInterface $uuid,
        private readonly ClockInterface $clock,
        private readonly TenantExecutionContextInterface $executionContext,
        private readonly Dispatcher $events,
        private readonly LoggerInterface $logger,
    ) {}

    public function enqueueStatusChanged(
        int $tenantId,
        string $previousStatus,
        string $newStatus,
        ?string $reason,
    ): string {
        return $this->executionContext->runForTenant($tenantId, function () use ($tenantId, $previousStatus, $newStatus, $reason): string {
            $eventId = $this->uuid->generate();
            $this->outbox->newQuery()->create([
                'event_uuid' => $eventId,
                'tenant_id' => $tenantId,
                'event_type' => self::STATUS_CHANGED,
                'payload' => [
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus,
                    'reason' => $reason,
                ],
                'status' => 'pending',
                'attempts' => 0,
                'available_at' => $this->clock->now(),
            ]);

            return $eventId;
        });
    }

    /** @return array{checked:int,published:int,failed:int} */
    public function publish(?int $limit = null): array
    {
        return $this->executionContext->runAsControlPlane(
            fn (): array => $this->publishAcrossTenants($limit),
        );
    }

    /** @return array{checked:int,published:int,failed:int} */
    private function publishAcrossTenants(?int $limit): array
    {
        $now = $this->clock->now();
        $batchSize = max(1, min($limit ?? 100, 500));
        $summary = ['checked' => 0, 'published' => 0, 'failed' => 0];
        $staleBefore = $now->modify('-'.self::CLAIM_TIMEOUT_MINUTES.' minutes');

        $candidates = $this->outbox->newQuery()
            ->where('available_at', '<=', $now)
            ->where(function ($query) use ($staleBefore): void {
                $query->where('status', 'pending')
                    ->orWhere(function ($stale) use ($staleBefore): void {
                        $stale->where('status', 'processing')
                            ->where('claimed_at', '<=', $staleBefore);
                    });
            })
            ->orderBy('available_at')
            ->orderBy('id')
            ->limit($batchSize)
            ->get(['id', 'tenant_id']);

        foreach ($candidates as $candidate) {
            $eventId = (int) $candidate->getKey();
            $tenantId = (int) $candidate->tenant_id;
            $claimToken = $this->uuid->generate();
            $claimed = $this->outbox->newQuery()
                ->whereKey($eventId)
                ->where('tenant_id', $tenantId)
                ->where('available_at', '<=', $now)
                ->where(function ($query) use ($staleBefore): void {
                    $query->where('status', 'pending')
                        ->orWhere(function ($stale) use ($staleBefore): void {
                            $stale->where('status', 'processing')
                                ->where('claimed_at', '<=', $staleBefore);
                        });
                })
                ->update([
                    'status' => 'processing',
                    'claim_token' => $claimToken,
                    'claimed_at' => $now,
                    'updated_at' => $now,
                ]);

            if ($claimed !== 1) {
                continue;
            }

            $event = $this->outbox->newQuery()
                ->whereKey($eventId)
                ->where('tenant_id', $tenantId)
                ->where('claim_token', $claimToken)
                ->first();
            if (! $event instanceof TenantEventOutboxModel) {
                continue;
            }

            $summary['checked']++;
            $payload = is_array($event->payload) ? $event->payload : [];

            try {
                if ((string) $event->event_type !== self::STATUS_CHANGED) {
                    throw new \RuntimeException('Unsupported tenant outbox event type.');
                }

                $this->executionContext->runForTenant(
                    $tenantId,
                    fn (): mixed => $this->events->dispatch(new TenantStatusChanged(
                        eventId: (string) $event->event_uuid,
                        tenantId: $tenantId,
                        previousStatus: (string) ($payload['previous_status'] ?? ''),
                        newStatus: (string) ($payload['new_status'] ?? ''),
                        reason: isset($payload['reason']) ? (string) $payload['reason'] : null,
                    )),
                );

                $this->outbox->newQuery()
                    ->whereKey($eventId)
                    ->where('tenant_id', $tenantId)
                    ->where('claim_token', $claimToken)
                    ->update([
                        'status' => 'published',
                        'published_at' => $now,
                        'claim_token' => null,
                        'claimed_at' => null,
                        'last_error' => null,
                        'updated_at' => $now,
                    ]);
                $summary['published']++;
            } catch (Throwable $exception) {
                $attempts = ((int) $event->attempts) + 1;
                $delayMinutes = min(60, 2 ** min($attempts, 6));
                $this->outbox->newQuery()
                    ->whereKey($eventId)
                    ->where('tenant_id', $tenantId)
                    ->where('claim_token', $claimToken)
                    ->update([
                        'status' => 'pending',
                        'attempts' => $attempts,
                        'last_error' => mb_substr($exception->getMessage(), 0, 500),
                        'available_at' => $now->modify("+{$delayMinutes} minutes"),
                        'claim_token' => null,
                        'claimed_at' => null,
                        'updated_at' => $now,
                    ]);
                $summary['failed']++;

                $this->logger->error('Tenant outbox event publication failed.', [
                    'event_uuid' => $event->event_uuid,
                    'tenant_id' => $event->tenant_id,
                    'event_type' => $event->event_type,
                    'attempts' => $attempts,
                    'exception' => $exception,
                ]);
            }
        }

        return $summary;
    }
}
