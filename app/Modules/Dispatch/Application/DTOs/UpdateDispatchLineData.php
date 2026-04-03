<?php

declare(strict_types=1);

namespace Modules\Dispatch\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdateDispatchLineData extends BaseDto
{
    public int $id;
    public ?string $description = null;
    public ?float $quantity = null;
    public ?int $warehouseLocationId = null;
    public ?string $batchNumber = null;
    public ?string $serialNumber = null;
    public ?float $weight = null;
    public ?string $notes = null;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'id'                 => 'required|integer',
            'description'        => 'nullable|string',
            'quantity'           => 'nullable|numeric|min:0',
            'warehouseLocationId'=> 'nullable|integer',
            'batchNumber'        => 'nullable|string|max:100',
            'serialNumber'       => 'nullable|string|max:100',
            'weight'             => 'nullable|numeric|min:0',
            'notes'              => 'nullable|string',
            'metadata'           => 'nullable|array',
        ];
    }
}
