<?php

declare(strict_types=1);

namespace Modules\ServiceCenter\Application\DTOs;

final class CreateServiceOrderDTO
{
    public function __construct(
        public readonly string $tenantId,
        public readonly string $assetId,
        public readonly ?string $assignedTechnicianId,
        public readonly string $serviceType,
        public readonly ?string $description,
        public readonly ?\DateTime $scheduledAt,
        public readonly string $estimatedCost,
        /** @var array<int, array{task_name: string, description: ?string, labor_cost: string, labor_minutes: ?int}> */
        public readonly array $tasks = [],
    ) {
    }
}
