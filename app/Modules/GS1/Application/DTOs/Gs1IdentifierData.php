<?php

declare(strict_types=1);

namespace Modules\GS1\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class Gs1IdentifierData extends BaseDto
{
    public int $tenantId;
    public string $identifierType;
    public string $identifierValue;
    public ?string $entityType = null;
    public ?int $entityId = null;
    public bool $isActive = true;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'tenantId'        => 'required|integer',
            'identifierType'  => 'required|string|in:gtin,gln,sscc,grai,giai,gcp',
            'identifierValue' => 'required|string|max:255',
            'entityType'      => 'nullable|string|max:100',
            'entityId'        => 'nullable|integer',
            'isActive'        => 'boolean',
            'metadata'        => 'nullable|array',
        ];
    }
}
