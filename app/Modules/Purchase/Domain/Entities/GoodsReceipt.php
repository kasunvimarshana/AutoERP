<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Entities;

final readonly class GoodsReceipt
{
    /**
     * @param array<int, array<string, mixed>> $lines
     * @param array<string, mixed> $attributes
     */
    public function __construct(public array $attributes, public array $lines = [])
    {
    }
}
