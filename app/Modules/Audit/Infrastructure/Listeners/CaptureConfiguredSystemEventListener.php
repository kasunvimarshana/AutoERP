<?php

declare(strict_types=1);

namespace Modules\Audit\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Audit\Application\Contracts\UseCases\AuditLogs\CaptureSystemEventAuditServiceInterface;

final class CaptureConfiguredSystemEventListener
{
    public function __construct(private readonly CaptureSystemEventAuditServiceInterface $captureSystemEventAudit)
    {
    }

    /**
     * @param array<int, mixed> $payload
     */
    public function handle(string $eventName, array $payload = []): void
    {
        try {
            if ($this->shouldSkip($eventName)) {
                return;
            }

            $eventPayload = $payload[0] ?? $payload;
            $result = $this->captureSystemEventAudit->execute($eventName, $eventPayload);

            if ($result->isFailure()) {
                $error = $result->errorOrFail();

                Log::warning('Audit event capture failed.', [
                    'event' => $eventName,
                    'error_code' => $error->code,
                    'error_message' => $error->message,
                    'error_context' => $error->context,
                ]);

                if (! (bool) config('audit.fail_safe.swallow_exceptions', true)) {
                    throw new \RuntimeException($error->message);
                }
            }
        } catch (\Throwable $exception) {
            Log::warning('Audit listener caught event capture exception.', [
                'event' => $eventName,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            if (! (bool) config('audit.fail_safe.swallow_exceptions', true)) {
                throw $exception;
            }
        }
    }

    private function shouldSkip(string $eventName): bool
    {
        foreach ((array) config('audit.events.ignore_prefixes', []) as $prefix) {
            if (is_string($prefix) && $prefix !== '' && str_starts_with($eventName, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
