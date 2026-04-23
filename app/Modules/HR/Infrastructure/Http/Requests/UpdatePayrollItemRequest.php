<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayrollItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:20',
            'type' => 'sometimes|string|in:earning,deduction,tax',
            'calculation_type' => 'sometimes|string|in:fixed,percentage',
            'value' => 'sometimes|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'is_taxable' => 'nullable|boolean',
            'account_id' => 'nullable|integer',
            'metadata' => 'nullable|array',
        ];
    }
}
