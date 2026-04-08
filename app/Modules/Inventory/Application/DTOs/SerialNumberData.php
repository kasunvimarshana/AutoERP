<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

final class SerialNumberData extends BaseDto
{
    public int $product_id;

    public ?int $variant_id = null;

    public string $serial_number;

    public string $status = 'available';

    public ?int $location_id = null;

    public ?string $manufacture_date = null;

    public ?string $expiry_date = null;

    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'product_id'       => ['required', 'integer'],
            'variant_id'       => ['nullable', 'integer'],
            'serial_number'    => ['required', 'string', 'max:255'],
            'status'           => ['string', 'in:available,reserved,sold,scrapped'],
            'location_id'      => ['nullable', 'integer'],
            'manufacture_date' => ['nullable', 'date'],
            'expiry_date'      => ['nullable', 'date'],
            'metadata'         => ['nullable', 'array'],
        ];
    }
}
