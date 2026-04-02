<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id'        => 'required|integer',
            'employee_id'      => 'required|integer',
            'pay_period_start' => 'required|date_format:Y-m-d',
            'pay_period_end'   => 'required|date_format:Y-m-d',
            'gross_salary'     => 'required|numeric|min:0',
            'net_salary'       => 'required|numeric|min:0',
            'deductions'       => 'nullable|numeric|min:0',
            'allowances'       => 'nullable|numeric|min:0',
            'bonuses'          => 'nullable|numeric|min:0',
            'currency'         => 'nullable|string|max:3',
            'status'           => 'nullable|string|in:draft,processed,paid',
            'notes'            => 'nullable|string',
            'metadata'         => 'nullable|array',
        ];
    }
}
