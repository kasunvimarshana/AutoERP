<?php

declare(strict_types=1);

namespace App\Listeners\Common;

use App\Core\Events\BaseEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Recalculate Metrics Listener
 * 
 * Recalculates relevant metrics when events occur
 */
class RecalculateMetricsListener implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 3;
    public int $timeout = 300;

    /**
     * Handle the event
     */
    public function handle(BaseEvent $event): void
    {
        try {
            $eventName = $event->getEventName();
            $tenantId = $event->tenantId;

            // Clear relevant caches
            $this->clearRelevantCaches($eventName, $tenantId);

            // Trigger metric recalculations based on event type
            match (true) {
                str_contains($eventName, 'Stock') => $this->recalculateInventoryMetrics($tenantId),
                str_contains($eventName, 'Order') => $this->recalculateSalesMetrics($tenantId),
                str_contains($eventName, 'Invoice') => $this->recalculateFinancialMetrics($tenantId),
                str_contains($eventName, 'Payment') => $this->recalculateFinancialMetrics($tenantId),
                str_contains($eventName, 'Customer') || str_contains($eventName, 'Lead') => $this->recalculateCRMMetrics($tenantId),
                default => null, // No specific metrics to recalculate
            };
        } catch (\Exception $e) {
            Log::error('Failed to recalculate metrics', [
                'event' => $event->getEventName(),
                'tenant_id' => $event->tenantId,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    private function clearRelevantCaches(string $eventName, int $tenantId): void
    {
        $cacheKeys = [
            "tenant:{$tenantId}:dashboard:metrics",
            "tenant:{$tenantId}:reports:summary",
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    private function recalculateInventoryMetrics(int $tenantId): void
    {
        // Placeholder for inventory metric recalculation
        // Implement specific business logic here
        Log::info('Recalculating inventory metrics', ['tenant_id' => $tenantId]);
    }

    private function recalculateSalesMetrics(int $tenantId): void
    {
        Log::info('Recalculating sales metrics', ['tenant_id' => $tenantId]);
    }

    private function recalculateFinancialMetrics(int $tenantId): void
    {
        Log::info('Recalculating financial metrics', ['tenant_id' => $tenantId]);
    }

    private function recalculateCRMMetrics(int $tenantId): void
    {
        Log::info('Recalculating CRM metrics', ['tenant_id' => $tenantId]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(BaseEvent $event, \Throwable $exception): void
    {
        Log::error('RecalculateMetricsListener failed', [
            'event' => $event->getEventName(),
            'tenant_id' => $event->tenantId,
            'error' => $exception->getMessage(),
        ]);
    }
}
