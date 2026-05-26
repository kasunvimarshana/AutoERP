<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\DTOs;

final readonly class CompleteJobCardDTO
{
    /** @param array<string, mixed> $payload */
    public function __construct(public int $jobCardId, public array $payload = [])
    {
    }
}
