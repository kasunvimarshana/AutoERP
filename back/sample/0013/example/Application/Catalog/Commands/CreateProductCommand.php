<?php

declare(strict_types=1);

namespace App\Application\Catalog\Commands;

final class CreateProductCommand
{
    public function __construct(
        public readonly string $name,
        public readonly int    $priceAmount,   // minor units
        public readonly string $priceCurrency,
    ) {}
}
