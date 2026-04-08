<?php

declare(strict_types=1);

namespace Modules\GS1\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class Gs1BarcodeData extends BaseDto
{
    public int $tenantId;
    public int $gs1IdentifierId;
    public string $barcodeType;
    public string $barcodeData;
    public ?string $applicationIdentifiers = null;
    public bool $isPrimary = false;
    public bool $isActive = true;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'tenantId'               => 'required|integer',
            'gs1IdentifierId'        => 'required|integer',
            'barcodeType'            => 'required|string|in:gs1_128,ean_13,ean_8,upc_a,datamatrix,qr_code',
            'barcodeData'            => 'required|string',
            'applicationIdentifiers' => 'nullable|string|max:1000',
            'isPrimary'              => 'boolean',
            'isActive'               => 'boolean',
            'metadata'               => 'nullable|array',
        ];
    }
}
