<?php

declare(strict_types=1);

namespace Modules\Barcode\Infrastructure\Generators;

use Modules\Barcode\Domain\Entities\BarcodeDefinition;
use Modules\Barcode\Domain\Exceptions\UnsupportedBarcodeTypeException;

/**
 * Routes a barcode-generation request to the correct driver adapter.
 *
 * Additional drivers can be registered at boot time via addDriver(), making
 * the dispatcher fully extensible without modifying existing code (Open/Closed
 * principle).
 */
class BarcodeGeneratorDispatcher
{
    /** @var array<string, BarcodeGeneratorDriverInterface> */
    private array $drivers = [];

    public function addDriver(string $type, BarcodeGeneratorDriverInterface $driver): void
    {
        $this->drivers[$type] = $driver;
    }

    /**
     * Generate a barcode for the given definition.
     *
     * @throws UnsupportedBarcodeTypeException when no driver handles the type.
     */
    public function generate(BarcodeDefinition $def, string $format, array $options): string
    {
        $type = $def->getType();

        $driver = $this->resolveDriver($type);

        return $driver->generate($def->getValue(), $format, $options);
    }

    /**
     * Validate a value against the registered driver for the given type.
     *
     * @throws UnsupportedBarcodeTypeException when no driver handles the type.
     */
    public function validate(string $type, string $value): bool
    {
        return $this->resolveDriver($type)->validate($value);
    }

    public function hasDriver(string $type): bool
    {
        return isset($this->drivers[$type]);
    }

    /**
     * Return every type constant for which a driver is registered.
     *
     * @return string[]
     */
    public function getSupportedTypes(): array
    {
        return array_keys($this->drivers);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * @throws UnsupportedBarcodeTypeException
     */
    private function resolveDriver(string $type): BarcodeGeneratorDriverInterface
    {
        if (!isset($this->drivers[$type])) {
            throw UnsupportedBarcodeTypeException::forType($type);
        }

        return $this->drivers[$type];
    }
}
