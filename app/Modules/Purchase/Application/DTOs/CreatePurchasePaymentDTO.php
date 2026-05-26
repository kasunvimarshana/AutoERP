<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\DTOs;

final readonly class CreatePurchasePaymentDTO
{
    /** @param array<string, mixed> $payload */
    public function __construct(public array $payload)
    {
    }
}
