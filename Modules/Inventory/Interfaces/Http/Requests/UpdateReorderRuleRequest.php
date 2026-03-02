<?php

declare(strict_types=1);

namespace Modules\Inventory\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReorderRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'reorder_point' => ['required', 'numeric', 'min:0'],
            'reorder_quantity' => ['required', 'numeric', 'min:0.0001'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
