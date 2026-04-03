<?php

declare(strict_types=1);

namespace Modules\GS1\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdateGs1BarcodeData extends BaseDto
{
    public int $id;
    public ?string $barcodeType = null;
    public ?string $barcodeData = null;
    public ?string $applicationIdentifiers = null;
    public ?bool $isPrimary = null;
    public ?bool $isActive = null;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'id'                     => 'required|integer',
            'barcodeType'            => 'sometimes|required|string|in:gs1_128,ean_13,ean_8,upc_a,datamatrix,qr_code',
            'barcodeData'            => 'sometimes|required|string',
            'applicationIdentifiers' => 'nullable|string|max:1000',
            'isPrimary'              => 'nullable|boolean',
            'isActive'               => 'nullable|boolean',
            'metadata'               => 'nullable|array',
        ];
    }
}
