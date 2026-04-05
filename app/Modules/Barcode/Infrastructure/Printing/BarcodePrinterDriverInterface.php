<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Printing;

use Modules\Barcode\Domain\Entities\BarcodeDefinition;
use Modules\Barcode\Domain\Entities\LabelTemplate;

/**
 * Contract for barcode printer drivers.
 *
 * Each driver supports a specific output format (e.g. ZPL, EPL, SVG) and
 * is responsible for rendering a label from a BarcodeDefinition and a
 * LabelTemplate (or a default layout when no template is provided).
 */
interface BarcodePrinterDriverInterface
{
    /**
     * The output format string this driver handles (e.g. "zpl", "epl", "svg").
     */
    public function getFormat(): string;

    /**
     * Render a printable label.
     *
     * @param  BarcodeDefinition $definition  The barcode to render
     * @param  LabelTemplate|null $template   Optional design template; if null the driver uses a sensible default layout
     * @param  array<string,string> $variables Extra placeholder substitution data
     * @return string                         Rendered output (ZPL program, EPL program, or SVG markup)
     */
    public function render(
        BarcodeDefinition $definition,
        ?LabelTemplate    $template,
        array             $variables,
    ): string;
}
