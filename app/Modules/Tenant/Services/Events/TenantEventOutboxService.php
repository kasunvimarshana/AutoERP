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
use RuntimeException;
use Throwable;

final class TenantEventOutboxService
{
    private const EVENT_STATUS_CHANGED = 'tenant.status_changed';
    private const STATUS_PENDING = 'pending';
    private const STATUS_PROCESSING = 'processing';
    private const STATUS_PUBLISHED = 'published';
    private const STATUS_DEAD = 'dead';

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
                'event_type' => self::EVENT_STATUS_CHANGED,
                'payload' => [
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus,
                    'reason' => $reason,
                ],
                'status' => self::STATUS_PENDING,
                'attempts' => 0,
                'available_at' => $this->clock->now(),
            ]);

            return $eventId;
        });
    }

    /** @return array{checked:int,published:int,failed:int,dead:int,purged:int,recovered:int} */
    public function publish(?int $limit = null): array
    {
        return $this->executionContext->runAsControlPlane(
            fn (): array => $this->publishAcrossTenants($limit),
        );
    }

    public function retryDead(?string $eventUuid = null, ?int $limit = null): int
    {
        return $this->executionContext->runAsControlPlane(function () use ($eventUuid, $limit): int {
            $query = $this->outbox->newQuery()->where('status', self::STATUS_DEAD);
            if (is_string($eventUuid) && trim($eventUuid) !== '') {
                $query->where('event_uuid', trim($eventUuid));
            } else {
                $query->limit(max(1, min($limit ?? 100, 500)));
            }

            $ids = $query->pluck('id')->all();
            if ($ids === []) {
                return 0;
            }

            return $this->outbox->newQuery()
                ->whereIn('id', $ids)
                ->where('status', self::STATUS_DEAD)
                ->update([
                    'status' => self::STATUS_PENDING,
                    'attempts' => 0,
                    'last_error_code' => null,
                    'last_error_message' => null,
                    'available_at' => $this->clock->now(),
                    'claim_token' => null,
                    'claimed_at' => null,
                    'dead_at' => null,
                    'updated_at' => $this->clock->now(),
                ]);
        });
    }

    /** @return array{checked:int,published:int,failed:int,dead:int,purged:int,recovered:int} */
    private function publishAcrossTenants(?int $limit): array
    {
        $summary = ['checked' => 0, 'published' => 0, 'failed' => 0, 'dead' => 0, 'purged' => 0, 'recovered' => 0];
        $summary['recovered'] = $this->recoverStaleClaims();
        $summary['purged'] = $this->purgePublished();
        $now = $this->clock->now();
        $batchSize = max(1, min($limit ?? (int) config('tenant.event_outbox.batch_size', 100), 500));

        $candidateIds = $this->outbox->newQuery()
            ->where('status', self::STATUS_PENDING)
            ->where('available_at', '<=', $now)
            ->orderBy('available_at')
            ->orderBy('id')
            ->limit($batchSize)
            ->pluck('id')
            ->all();

        foreach ($candidateIds as $candidateId) {
            $event = $this->claim((int) $candidateId);
            if (! $event instanceof TenantEventOutboxModel) {
                continue;
            }

            $summary['checked']++;
            $tenantId = (int) $event->getAttribute('tenant_id');
            $claimToken = (string) $event->getAttribute('claim_token');
            $payload = is_array($event->getAttribute('payload')) ? $event->getAttribute('payload') : [];

            try {
                if ((string) $event->getAttribute('event_type') !== self::EVENT_STATUS_CHANGED) {
                    throw new RuntimeException('Unsupported tenant outbox event type.');
                }

                $this->executionContext->runForTenant(
                    $tenantId,
                    fn (): mixed => $this->events->dispatch(new TenantStatusChanged(
                        eventId: (string) $event->getAttribute('event_uuid'),
                        tenantId: $tenantId,
                        previousStatus: (string) ($payload['previous_status'] ?? ''),
                        newStatus: (string) ($payload['new_status'] ?? ''),
                        reason: isset($payload['reason']) ? (string) $payload['reason'] : null,
                    )),
                );

                $updated = $this->outbox->newQuery()
                    ->whereKey($event->getKey())
                    ->where('claim_token', $claimToken)
                    ->where('status', self::STATUS_PROCESSING)
                    ->update([
                        'status' => self::STATUS_PUBLISHED,
                        'published_at' => $this->clock->now(),
                        'claim_token' => null,
                        'claimed_at' => null,
                        'last_error_code' => null,
                    'last_error_message' => null,
                        'updated_at' => $this->clock->now(),
                    ]);
                if ($updated === 1) {
                    $summary['published']++;
                }
            } catch (Throwable $exception) {
                $attempts = ((int) $event->getAttribute('attempts')) + 1;
                $maxAttempts = max(1, (int) config('tenant.event_outbox.max_attempts', 10));
                $dead = $attempts >= $maxAttempts;
                $delayMinutes = min(60, 2 ** min($attempts, 6));

                $this->outbox->newQuery()
                    ->whereKey($event->getKey())
                    ->where('claim_token', $claimToken)
                    ->where('status', self::STATUS_PROCESSING)
                    ->update([
                        'status' => $dead ? self::STATUS_DEAD : self::STATUS_PENDING,
                        'attempts' => $attempts,
                        'last_error_code' => 'TENANT_EVENT_PUBLICATION_FAILED',
                        'last_error_message' => 'The event could not be delivered to its registered consumers.',
                        'available_at' => $dead ? $this->clock->now() : $this->clock->now()->modify("+{$delayMinutes} minutes"),
                        'claim_token' => null,
                        'claimed_at' => null,
                        'dead_at' => $dead ? $this->clock->now() : null,
                        'updated_at' => $this->clock->now(),
                    ]);
                $summary[$dead ? 'dead' : 'failed']++;

                $this->logger->error('Tenant outbox event publication failed.', [
                    'event_uuid' => $event->getAttribute('event_uuid'),
                    'tenant_id' => $tenantId,
                    'event_type' => $event->getAttribute('event_type'),
                    'attempts' => $attempts,
                    'dead' => $dead,
                    'exception' => $exception,
                ]);
            }
        }

        return $summary;
    }

    private function claim(int $eventId): ?TenantEventOutboxModel
    {
        $token = $this->uuid->generate();
        $updated = $this->outbox->newQuery()
            ->whereKey($eventId)
            ->where('status', self::STATUS_PENDING)
            ->where('available_at', '<=', $this->clock->now())
            ->update([
                'status' => self::STATUS_PROCESSING,
                'claim_token' => $token,
                'claimed_at' => $this->clock->now(),
                'updated_at' => $this->clock->now(),
            ]);

        if ($updated !== 1) {
            return null;
        }

        return $this->outbox->newQuery()
            ->whereKey($eventId)
            ->where('claim_token', $token)
            ->first();
    }

    private function recoverStaleClaims(): int
    {
        $timeout = max(60, (int) config('tenant.event_outbox.claim_timeout_seconds', 600));
        $threshold = $this->clock->now()->modify("-{$timeout} seconds");

        return $this->outbox->newQuery()
            ->where('status', self::STATUS_PROCESSING)
            ->whereNotNull('claimed_at')
            ->where('claimed_at', '<=', $threshold)
            ->update([
                'status' => self::STATUS_PENDING,
                'claim_token' => null,
                'claimed_at' => null,
                'available_at' => $this->clock->now(),
                'updated_at' => $this->clock->now(),
            ]);
    }

    private function purgePublished(): int
    {
        $retentionDays = max(1, (int) config('tenant.event_outbox.published_retention_days', 30));
        $threshold = $this->clock->now()->modify("-{$retentionDays} days");

        return $this->outbox->newQuery()
            ->where('status', self::STATUS_PUBLISHED)
            ->where('published_at', '<=', $threshold)
            ->delete();
    }
}
