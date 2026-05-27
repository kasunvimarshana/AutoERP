<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Finance\Application\Contracts\UseCases\FinanceCore\GenerateJournalEntryFromEventServiceInterface;

final class GenerateJournalEntryFromEventListener
{
    public function __construct(
        private readonly GenerateJournalEntryFromEventServiceInterface $generateJournalEntryFromEvent,
    ) {
    }

    /**
     * @param array<int, mixed> $payload
     */
    public function handle(string $eventName, array $payload = []): void
    {
        try {
            $eventPayload = $payload[0] ?? $payload;
            if (! is_array($eventPayload)) {
                return;
            }

            $result = $this->generateJournalEntryFromEvent->execute($eventPayload);
            if ($result->isFailure()) {
                $error = $result->errorOrFail();

                Log::warning('Finance core event processing failed.', [
                    'event' => $eventName,
                    'error_code' => $error->code,
                    'error_message' => $error->message,
                    'error_context' => $error->context,
                ]);

                if (! (bool) config('finance.core.fail_safe.swallow_exceptions', true)) {
                    throw new \RuntimeException($error->message);
                }
            }
        } catch (\Throwable $exception) {
            Log::warning('Finance core listener swallowed exception.', [
                'event' => $eventName,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            if (! (bool) config('finance.core.fail_safe.swallow_exceptions', true)) {
                throw $exception;
            }
        }
    }
}
