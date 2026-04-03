<?php

declare(strict_types=1);

namespace Modules\GS1\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdateGs1IdentifierData extends BaseDto
{
    public int $id;
    public ?string $identifierType = null;
    public ?string $identifierValue = null;
    public ?string $entityType = null;
    public ?int $entityId = null;
    public ?bool $isActive = null;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'id'              => 'required|integer',
            'identifierType'  => 'sometimes|required|string|in:gtin,gln,sscc,grai,giai,gcp',
            'identifierValue' => 'sometimes|required|string|max:255',
            'entityType'      => 'nullable|string|max:100',
            'entityId'        => 'nullable|integer',
            'isActive'        => 'nullable|boolean',
            'metadata'        => 'nullable|array',
        ];
    }
}
