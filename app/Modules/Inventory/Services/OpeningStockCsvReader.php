<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

final class OpeningStockCsvReader
{
    /** @var list<string> */
    public const HEADERS = [
        'item_code',
        'variant_code',
        'uom_code',
        'opening_quantity',
        'unit_cost',
        'batch_number',
        'serial_number',
        'reason',
    ];

    private const REQUIRED_HEADERS = ['item_code', 'opening_quantity', 'unit_cost'];

    /**
     * @return list<array{row_number: int, values: array<string, string>, errors: list<string>}>
     */
    public function rows(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        if (! is_string($path)) {
            throw new InvalidArgumentException('Opening stock CSV could not be read.');
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new InvalidArgumentException('Opening stock CSV could not be opened.');
        }

        try {
            $headerRow = fgetcsv($handle, null, ',', '"', '');
            if (! is_array($headerRow)) {
                throw new InvalidArgumentException('Opening stock CSV header row is missing.');
            }

            $headers = array_map(
                static fn (mixed $header): string => strtolower(trim((string) $header)),
                $headerRow,
            );
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]) ?? $headers[0];
            $this->assertHeaders($headers);

            $rows = [];
            $rowNumber = 1;
            $maxRows = (int) config('inventory.opening_stock_import.max_rows');
            while (($columns = fgetcsv($handle, null, ',', '"', '')) !== false) {
                $rowNumber++;
                if ($this->emptyRow($columns)) {
                    continue;
                }
                if (count($rows) >= $maxRows) {
                    throw new InvalidArgumentException("Opening stock CSV cannot contain more than {$maxRows} data rows.");
                }

                $errors = [];
                if (count($columns) !== count($headers)) {
                    $errors[] = 'Column count does not match the CSV header.';
                }
                $columns = array_slice(array_pad($columns, count($headers), ''), 0, count($headers));
                $mappedValues = array_combine($headers, array_map(
                    static fn (mixed $value): string => trim((string) $value),
                    $columns,
                ));
                if (! is_array($mappedValues)) {
                    throw new InvalidArgumentException('Opening stock CSV columns could not be mapped.');
                }

                $rows[] = [
                    'row_number' => $rowNumber,
                    'values' => array_replace(array_fill_keys(self::HEADERS, ''), $mappedValues),
                    'errors' => $errors,
                ];
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    /** @param list<string> $headers */
    private function assertHeaders(array $headers): void
    {
        if (count($headers) !== count(array_unique($headers))) {
            throw new InvalidArgumentException('Opening stock CSV contains duplicate column headings.');
        }

        $missing = array_values(array_diff(self::REQUIRED_HEADERS, $headers));
        if ($missing !== []) {
            throw new InvalidArgumentException('Opening stock CSV is missing required columns: '.implode(', ', $missing).'.');
        }

        $unknown = array_values(array_diff($headers, self::HEADERS));
        if ($unknown !== []) {
            throw new InvalidArgumentException('Opening stock CSV contains unsupported columns: '.implode(', ', $unknown).'.');
        }
    }

    /** @param array<int, mixed> $columns */
    private function emptyRow(array $columns): bool
    {
        foreach ($columns as $column) {
            if (trim((string) $column) !== '') {
                return false;
            }
        }

        return true;
    }
}
