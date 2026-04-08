<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class InventoryAdjustmentData extends BaseDto
{
    public ?string $id = null;
    public ?string $adjustmentNumber = null;
    public string $adjustmentDate = '';
    public string $warehouseId = '';
    public string $type = 'recount';
    public string $status = 'draft';
    public ?string $reason = null;
    public ?string $notes = null;
    public array $lines = [];

    public function rules(): array
    {
        return [
            'adjustment_date' => ['required', 'date'],
            'warehouse_id'    => ['required', 'string'],
            'type'            => ['sometimes', 'string', 'in:increase,decrease,recount,write_off,write_on'],
            'lines'           => ['required', 'array', 'min:1'],
        ];
    }
}
