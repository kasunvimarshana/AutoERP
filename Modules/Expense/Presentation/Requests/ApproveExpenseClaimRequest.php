<?php

namespace Modules\Expense\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveExpenseClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'approver_id' => ['required', 'string'],
        ];
    }
}
