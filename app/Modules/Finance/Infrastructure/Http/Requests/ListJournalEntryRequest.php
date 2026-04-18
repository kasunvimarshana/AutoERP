<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['sometimes', 'integer', 'exists:tenants,id'],
            'fiscal_period_id' => ['sometimes', 'integer', 'exists:fiscal_periods,id'],
            'entry_type' => ['sometimes', 'in:manual,auto,system'],
            'status' => ['sometimes', 'in:draft,posted,reversed'],
            'entry_number' => ['sometimes', 'string', 'max:255'],
            'reference_type' => ['sometimes', 'string', 'max:255'],
            'reference_id' => ['sometimes', 'integer'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'sort' => ['sometimes', 'string', 'max:50'],
        ];
    }
}
