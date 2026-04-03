<?php

declare(strict_types=1);

namespace Modules\Taxation\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaxRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'sometimes|nullable|string|max:255',
            'tax_rate_id' => 'sometimes|nullable|integer',
            'entity_type' => 'sometimes|nullable|string|in:product,category,customer,supplier',
            'entity_id'   => 'nullable|integer',
            'jurisdiction'=> 'nullable|string|max:255',
            'priority'    => 'sometimes|nullable|integer',
            'is_active'   => 'sometimes|nullable|boolean',
            'description' => 'nullable|string',
            'metadata'    => 'nullable|array',
        ];
    }
}
