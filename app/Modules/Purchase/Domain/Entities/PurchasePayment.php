<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Entities;

final readonly class PurchasePayment
{
    /**
     * @param array<int, array<string, mixed>> $allocations
     * @param array<string, mixed> $attributes
     */
    public function __construct(public array $attributes, public array $allocations = [])
    {
    }
}
