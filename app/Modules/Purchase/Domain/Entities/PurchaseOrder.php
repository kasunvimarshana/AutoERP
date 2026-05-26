<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Entities;

final readonly class PurchaseOrder
{
    /**
     * @param array<int, array<string, mixed>> $lines
     * @param array<string, mixed> $attributes
     */
    public function __construct(public array $attributes, public array $lines = [])
    {
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<int, array<string, mixed>> $lines
     */
    public static function fromArray(array $attributes, array $lines = []): self
    {
        return new self($attributes, $lines);
    }
}
