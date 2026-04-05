<?php

declare(strict_types=1);

namespace Modules\Barcode\Application\Services;

use Modules\Barcode\Application\Contracts\GenerateBarcodeServiceInterface;
use Modules\Barcode\Domain\Entities\BarcodeDefinition;
use Modules\Barcode\Domain\ValueObjects\BarcodeOutputFormat;
use Modules\Barcode\Infrastructure\Generators\BarcodeGeneratorDispatcher;

class GenerateBarcodeService implements GenerateBarcodeServiceInterface
{
    public function __construct(
        private readonly BarcodeGeneratorDispatcher $dispatcher,
    ) {}

    public function generate(
        BarcodeDefinition $def,
        string $format = BarcodeOutputFormat::SVG,
        array $options = [],
    ): string {
        return $this->dispatcher->generate($def, $format, $options);
    }
}
