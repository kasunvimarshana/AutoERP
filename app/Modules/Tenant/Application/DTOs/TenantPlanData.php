<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class TenantPlanData extends BaseDto
{
    public ?int $id = null;

    public string $name;

    public string $slug;

    public ?array $features = null;

    public ?array $limits = null;

    public string $price = '0.0000';

    public string $currency_code = 'USD';

    public string $billing_interval = 'month';

    public bool $is_active = true;

    public function rules(): array
    {
        $excludeId = $this->id ? ",{$this->id}" : '';

        return [
            'name' => 'required|string|max:255',
            'slug' => "required|string|max:127|unique:tenant_plans,slug{$excludeId}",
            'features' => 'nullable|array',
            'limits' => 'nullable|array',
            'price' => 'required|numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'billing_interval' => 'required|in:month,year',
            'is_active' => 'required|boolean',
        ];
    }
}
