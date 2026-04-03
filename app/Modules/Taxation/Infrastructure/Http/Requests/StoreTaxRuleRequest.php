<?php

declare(strict_types=1);

namespace Modules\Taxation\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaxRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'   => 'required|integer',
            'name'        => 'required|string|max:255',
            'tax_rate_id' => 'required|integer',
            'entity_type' => 'required|string|in:product,category,customer,supplier',
            'entity_id'   => 'nullable|integer',
            'jurisdiction'=> 'nullable|string|max:255',
            'priority'    => 'integer',
            'is_active'   => 'boolean',
            'description' => 'nullable|string',
            'metadata'    => 'nullable|array',
        ];
    }
}
