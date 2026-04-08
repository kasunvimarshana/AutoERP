<?php

declare(strict_types=1);

namespace Modules\Taxation\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class TaxRuleData extends BaseDto
{
    public int $tenantId;
    public string $name;
    public int $taxRateId;
    public string $entityType;
    public ?int $entityId = null;
    public ?string $jurisdiction = null;
    public int $priority = 0;
    public bool $isActive = true;
    public ?string $description = null;
    public ?array $metadata = null;

    public function rules(): array
    {
        return [
            'tenantId'    => 'required|integer',
            'name'        => 'required|string|max:255',
            'taxRateId'   => 'required|integer',
            'entityType'  => 'required|string|in:product,category,customer,supplier',
            'entityId'    => 'nullable|integer',
            'jurisdiction'=> 'nullable|string|max:255',
            'priority'    => 'integer',
            'isActive'    => 'boolean',
            'description' => 'nullable|string',
            'metadata'    => 'nullable|array',
        ];
    }
}
