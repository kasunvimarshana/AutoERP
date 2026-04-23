<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePayrollItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20',
            'type' => 'required|string|in:earning,deduction,tax',
            'calculation_type' => 'required|string|in:fixed,percentage',
            'value' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'is_taxable' => 'nullable|boolean',
            'account_id' => 'nullable|integer',
            'metadata' => 'nullable|array',
        ];
    }
}
