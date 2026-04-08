<?php

declare(strict_types=1);

namespace Modules\Taxation\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UpdateTaxRuleData extends BaseDto
{
    public int $id;
    public ?string $name = null;
    public ?int $taxRateId = null;
    public ?string $entityType = null;
    public ?int $entityId = null;
    public ?string $jurisdiction = null;
    public ?int $priority = null;
    public ?bool $isActive = null;
    public ?string $description = null;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'id'          => 'required|integer',
            'name'        => 'sometimes|nullable|string|max:255',
            'taxRateId'   => 'sometimes|nullable|integer',
            'entityType'  => 'sometimes|nullable|string|in:product,category,customer,supplier',
            'entityId'    => 'nullable|integer',
            'jurisdiction'=> 'nullable|string|max:255',
            'priority'    => 'sometimes|nullable|integer',
            'isActive'    => 'sometimes|nullable|boolean',
            'description' => 'nullable|string',
            'metadata'    => 'nullable|array',
        ];
    }
}
