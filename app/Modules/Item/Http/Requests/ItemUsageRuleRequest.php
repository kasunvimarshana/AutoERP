<?php

declare(strict_types=1);

namespace Modules\Item\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Item\DTOs\ItemUsageRuleData;

abstract class ItemUsageRuleRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'module_code' => ['required', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_]*$/'],
            'is_enabled' => ['nullable', 'boolean'],
        ];
    }

    public function toData(): ItemUsageRuleData
    {
        return new ItemUsageRuleData(
            moduleCode: (string) $this->input('module_code'),
            isEnabled: $this->boolean('is_enabled', true),
        );
    }
}
