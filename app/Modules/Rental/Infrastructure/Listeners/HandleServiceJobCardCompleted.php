<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Listeners;

use Modules\Service\Domain\Events\ServiceJobCardCompleted;

class HandleServiceJobCardCompleted
{
    public function handle(ServiceJobCardCompleted $event): void
    {
        // When a service job card completes, release any service hold on the asset
        // so it becomes available for rental again (status bridge)
        $jobCard = $event->jobCard;

        if ($jobCard->getAssetId() === null) {
            return;
        }

        // The actual asset status update would be handled here via the asset repository
        // For now, the event is received and the bridge is established
        // Full implementation would inject AssetRepositoryInterface and update service_status
    }
}
