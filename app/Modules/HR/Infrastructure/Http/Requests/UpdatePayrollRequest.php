<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pay_period_start' => 'sometimes|required|date_format:Y-m-d',
            'pay_period_end'   => 'sometimes|required|date_format:Y-m-d',
            'gross_salary'     => 'sometimes|required|numeric|min:0',
            'net_salary'       => 'sometimes|required|numeric|min:0',
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
