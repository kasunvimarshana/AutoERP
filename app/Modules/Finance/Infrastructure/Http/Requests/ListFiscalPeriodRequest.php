<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListFiscalPeriodRequest extends FormRequest
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
            'fiscal_year_id' => ['sometimes', 'integer', 'exists:fiscal_years,id'],
            'period_number' => ['sometimes', 'integer', 'min:1'],
            'name' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'in:open,closed'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'sort' => ['sometimes', 'string', 'max:50'],
        ];
    }
}
