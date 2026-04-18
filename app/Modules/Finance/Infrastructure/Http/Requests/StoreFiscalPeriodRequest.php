<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFiscalPeriodRequest extends FormRequest
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
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'fiscal_year_id' => ['required', 'integer', 'exists:fiscal_years,id'],
            'period_number' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', 'in:open,closed'],
        ];
    }
}
