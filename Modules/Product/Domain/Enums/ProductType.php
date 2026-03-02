<?php

declare(strict_types=1);

namespace Modules\Product\Domain\Enums;

enum ProductType: string
{
    case SINGLE   = 'single';
    case VARIABLE = 'variable';
    case COMBO    = 'combo';
    case SERVICE  = 'service';

    public function label(): string
    {
        return match ($this) {
            self::SINGLE   => 'Single Product',
            self::VARIABLE => 'Variable Product',
            self::COMBO    => 'Combo / Bundle',
            self::SERVICE  => 'Service',
        };
    }

    public function isPhysical(): bool
    {
        return $this !== self::SERVICE;
    }

    public function supportsVariants(): bool
    {
        return $this === self::VARIABLE;
    }
}
