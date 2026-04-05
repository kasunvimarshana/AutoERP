<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Generators;

/**
 * Contract for a single barcode-type generation adapter.
 *
 * Implementations are registered in BarcodeGeneratorDispatcher by type name.
 */
interface BarcodeGeneratorDriverInterface
{
    /**
     * Returns true when this driver handles the given barcode type constant.
     */
    public function supports(string $type): bool;

    /**
     * Generate and return the barcode representation.
     *
     * @param  string $value   The data to encode.
     * @param  string $format  One of BarcodeOutputFormat constants (svg, png_base64, raw).
     * @param  array  $options Driver-specific rendering options (height, bar_width, label, …).
     * @return string          Rendered output (SVG markup, base64 PNG, or raw data).
     *
     * @throws \InvalidArgumentException when $value is invalid for this symbology.
     */
    public function generate(string $value, string $format, array $options): string;

    /**
     * Validate that $value can be encoded by this symbology.
     */
    public function validate(string $value): bool;
}
