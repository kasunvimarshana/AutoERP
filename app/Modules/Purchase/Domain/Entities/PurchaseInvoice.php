<?php

declare(strict_types=1);

namespace Modules\Purchase\Domain\Entities;

final readonly class PurchaseInvoice
{
    /**
     * @param array<int, array<string, mixed>> $lines
     * @param array<int, array<string, mixed>> $references
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public array $attributes,
        public array $lines = [],
        public array $references = [],
    ) {
    }
}
