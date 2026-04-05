<?php

declare(strict_types=1);

namespace Modules\Barcode\Application\Contracts;

use Modules\Barcode\Domain\Entities\BarcodeDefinition;
use Modules\Barcode\Domain\ValueObjects\BarcodeOutputFormat;

interface GenerateBarcodeServiceInterface
{
    public function generate(
        BarcodeDefinition $def,
        string $format = BarcodeOutputFormat::SVG,
        array $options = [],
    ): string;
}
