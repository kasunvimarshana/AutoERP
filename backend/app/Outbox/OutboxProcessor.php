<?php

namespace App\Outbox;

use App\MessageBroker\Contracts\MessageBrokerInterface;
use App\Models\OutboxMessage;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Log;

/**
 * Polls the outbox table and forwards pending messages to the message broker
 * and webhook subscribers.
 *
 * Designed to be called periodically by the `outbox:process` Artisan command
 * or a queue worker. Processing is idempotent: messages already published or
 * failed are skipped.
 */
class OutboxProcessor
{
    public function __construct(
        private readonly MessageBrokerInterface $broker,
        private readonly WebhookService $webhookService
    ) {
    }

    /**
     * Process a batch of pending outbox messages.
     *
     * @param  int  $batchSize  Maximum messages to process in one call.
     * @return int  Number of messages successfully published.
     */
    public function process(int $batchSize = 50): int
    {
        $messages = OutboxMessage::pending()
            ->orderBy('created_at')
            ->limit($batchSize)
            ->get();

        $published = 0;

        foreach ($messages as $message) {
            if ($this->publishMessage($message)) {
                $published++;
            }
        }

        return $published;
    }

    /**
     * Retry all failed outbox messages (resets them to pending).
     *
     * @return int Number of messages reset.
     */
    public function retryFailed(): int
    {
        $failed = OutboxMessage::failed()->get();

        foreach ($failed as $message) {
            $message->resetToPending();
        }

        return $failed->count();
    }

    /**
     * Publish a single outbox message to the broker and dispatch webhooks.
     */
    private function publishMessage(OutboxMessage $message): bool
    {
        try {
            // Publish to message broker
            $this->broker->publish($message->event_type, $message->payload);

            // Dispatch webhooks asynchronously for this event
            $this->webhookService->dispatchWebhook(
                $message->event_type,
                $message->payload,
                $message->tenant_id
            );

            $message->markPublished();

            Log::info("[OutboxProcessor] Published outbox message #{$message->id}", [
                'event_type'     => $message->event_type,
                'aggregate_type' => $message->aggregate_type,
                'aggregate_id'   => $message->aggregate_id,
            ]);

            return true;
        } catch (\Throwable $e) {
            $message->markFailed($e->getMessage());

            Log::error("[OutboxProcessor] Failed to publish outbox message #{$message->id}: " . $e->getMessage(), [
                'event_type' => $message->event_type,
                'attempts'   => $message->attempts,
            ]);

            return false;
        }
    }
}
