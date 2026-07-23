<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Illuminate\Http\UploadedFile;
use Modules\Core\Http\Requests\TenantScopedRequest;

final class OpeningStockImportRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'adjustment_date' => ['required', 'date'],
            'warehouse_id' => ['required', 'integer', 'min:1'],
            'warehouse_location_id' => ['nullable', 'integer', 'min:1'],
            'csv_file' => [
                'required',
                'file',
                'extensions:csv',
                'max:'.(int) config('inventory.opening_stock_import.max_file_kilobytes'),
            ],
        ];
    }

    public function csvFile(): UploadedFile
    {
        /** @var UploadedFile $file */
        $file = $this->file('csv_file');

        return $file;
    }

    public function warehouseId(): int
    {
        return (int) $this->input('warehouse_id');
    }

    public function warehouseLocationId(): ?int
    {
        return $this->filled('warehouse_location_id')
            ? (int) $this->input('warehouse_location_id')
            : null;
    }
}
