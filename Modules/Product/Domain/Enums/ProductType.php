<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Enums;

enum ProductType: string
{
    case Stockable = 'stockable';
    case Consumable = 'consumable';
    case Service = 'service';
    case Digital = 'digital';
    case Bundle = 'bundle';
    case Composite = 'composite';
    /** Variant-based product: a configurable template whose purchasable/saleable
     *  units are distinct variants (e.g. a T-shirt sold in multiple sizes/colours).
     *  Variant management infrastructure is planned; this type is registered now
     *  so API consumers can classify products correctly from day one. */
    case Variant = 'variant';

    public function label(): string
    {
        return match ($this) {
            ProductType::Stockable => 'Stockable',
            ProductType::Consumable => 'Consumable',
            ProductType::Service => 'Service',
            ProductType::Digital => 'Digital',
            ProductType::Bundle => 'Bundle',
            ProductType::Composite => 'Composite',
            ProductType::Variant => 'Variant',
        };
    }
}
