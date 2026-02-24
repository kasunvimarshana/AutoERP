<?php

namespace Modules\Expense\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id'              => ['required', 'string'],
            'title'                    => ['required', 'string', 'max:255'],
            'description'              => ['nullable', 'string'],
            'currency'                 => ['nullable', 'string', 'size:3'],
            'lines'                    => ['required', 'array', 'min:1'],
            'lines.*.description'      => ['required', 'string'],
            'lines.*.expense_date'     => ['required', 'date'],
            'lines.*.amount'           => ['required', 'numeric', 'min:0'],
            'lines.*.expense_category_id' => ['nullable', 'string'],
            'lines.*.receipt_path'     => ['nullable', 'string'],
        ];
    }
}
