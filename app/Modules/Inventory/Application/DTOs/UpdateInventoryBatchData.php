<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdateInventoryBatchData extends BaseDto
{
    public int $id;

    public ?string $lotNumber = null;

    public ?string $manufactureDate = null;

    public ?string $expiryDate = null;

    public ?string $bestBeforeDate = null;

    public ?string $supplierBatchRef = null;

    public ?float $unitCost = null;

    public ?string $currency = null;

    public ?string $status = null;

    public ?string $notes = null;

    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'id'               => 'required|integer',
            'lotNumber'        => 'sometimes|nullable|string|max:100',
            'manufactureDate'  => 'sometimes|nullable|date',
            'expiryDate'       => 'sometimes|nullable|date',
            'bestBeforeDate'   => 'sometimes|nullable|date',
            'supplierBatchRef' => 'sometimes|nullable|string|max:255',
            'unitCost'         => 'sometimes|numeric|min:0',
            'currency'         => 'sometimes|string|size:3',
            'status'           => 'sometimes|string|in:active,quarantine,expired,depleted,recalled',
            'notes'            => 'sometimes|nullable|string',
            'metadata'         => 'nullable|array',
        ];
    }
}
