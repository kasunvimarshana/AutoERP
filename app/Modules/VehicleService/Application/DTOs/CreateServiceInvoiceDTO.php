<?php

declare(strict_types=1);

namespace Modules\VehicleService\Application\DTOs;

final readonly class CreateServiceInvoiceDTO
{
    public function __construct(public int $jobCardId)
    {
    }
}
