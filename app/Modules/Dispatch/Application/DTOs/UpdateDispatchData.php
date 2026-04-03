<?php

declare(strict_types=1);

namespace Modules\Dispatch\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdateDispatchData extends BaseDto
{
    public int $id;
    public ?string $customerReference = null;
    public ?string $estimatedDeliveryDate = null;
    public ?string $carrier = null;
    public ?string $trackingNumber = null;
    public ?string $notes = null;
    public ?array $metadata = null;
    public ?float $totalWeight = null;

    public function rules(): array
    {
        return [
            'id'                   => 'required|integer',
            'customerReference'    => 'nullable|string|max:100',
            'estimatedDeliveryDate'=> 'nullable|date',
            'carrier'              => 'nullable|string|max:100',
            'trackingNumber'       => 'nullable|string|max:100',
            'notes'                => 'nullable|string',
            'metadata'             => 'nullable|array',
            'totalWeight'          => 'nullable|numeric|min:0',
        ];
    }
}
