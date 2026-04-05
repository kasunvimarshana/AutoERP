<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Printing;

use Modules\Barcode\Domain\Entities\BarcodeDefinition;
use Modules\Barcode\Domain\Entities\LabelTemplate;
use Modules\Barcode\Domain\Exceptions\UnsupportedBarcodeTypeException;

/**
 * Routes a label-render request to the correct printer driver by format.
 *
 * Drivers are registered via addDriver() at boot time (Open/Closed Principle);
 * no modification of this class is needed to support new formats.
 */
class BarcodePrinterDispatcher
{
    /** @var array<string, BarcodePrinterDriverInterface> keyed by format string */
    private array $drivers = [];

    public function addDriver(BarcodePrinterDriverInterface $driver): void
    {
        $this->drivers[$driver->getFormat()] = $driver;
    }

    public function hasDriver(string $format): bool
    {
        return isset($this->drivers[$format]);
    }

    /** @return string[] */
    public function getSupportedFormats(): array
    {
        return array_keys($this->drivers);
    }

    /**
     * Render a label using the driver registered for the given format.
     *
     * @param  array<string,string> $variables
     * @throws UnsupportedBarcodeTypeException when no driver handles the format.
     */
    public function render(
        string            $format,
        BarcodeDefinition $definition,
        ?LabelTemplate    $template,
        array             $variables = [],
    ): string {
        if (!isset($this->drivers[$format])) {
            throw new UnsupportedBarcodeTypeException(
                sprintf('No printer driver registered for format "%s".', $format)
            );
        }

        return $this->drivers[$format]->render($definition, $template, $variables);
    }
}
