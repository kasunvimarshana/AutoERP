<?php

declare(strict_types=1);

namespace Modules\Inventory\DTOs;

final readonly class OpeningStockImportPreviewData
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<StockAdjustmentLineData>  $lines
     */
    public function __construct(
        public string $fileName,
        public array $rows,
        public array $lines,
    ) {}

    public function isValid(): bool
    {
        return $this->rows !== []
            && count($this->lines) === count($this->rows);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $validRows = count(array_filter(
            $this->rows,
            static fn (array $row): bool => $row['errors'] === [],
        ));

        return [
            'file_name' => $this->fileName,
            'total_rows' => count($this->rows),
            'valid_rows' => $validRows,
            'invalid_rows' => count($this->rows) - $validRows,
            'can_create' => $this->isValid(),
            'rows' => $this->rows,
        ];
    }
}
