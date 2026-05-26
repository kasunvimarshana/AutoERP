<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Entities;

final readonly class PurchaseReturnLine
{
    /** @param array<string, mixed> $attributes */
    public function __construct(public array $attributes)
    {
    }
}
