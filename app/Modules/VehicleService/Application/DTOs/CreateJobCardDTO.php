<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\DTOs;

final readonly class CreateJobCardDTO
{
    /** @param array<string, mixed> $payload */
    public function __construct(public array $payload)
    {
    }
}
