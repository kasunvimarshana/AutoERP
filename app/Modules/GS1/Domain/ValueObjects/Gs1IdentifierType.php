<?php

declare(strict_types=1);

namespace Modules\GS1\Domain\ValueObjects;

class Gs1IdentifierType
{
    public const GTIN = 'gtin';
    public const GLN  = 'gln';
    public const SSCC = 'sscc';
    public const GRAI = 'grai';
    public const GIAI = 'giai';
    public const GCP  = 'gcp';

    public static function values(): array
    {
        return ['gtin', 'gln', 'sscc', 'grai', 'giai', 'gcp'];
    }
}
