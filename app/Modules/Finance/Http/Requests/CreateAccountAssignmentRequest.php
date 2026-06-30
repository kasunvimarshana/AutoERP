<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateAccountAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'account_role_id' => ['required', 'integer', 'min:1'],
            'account_id' => ['required', 'integer', 'min:1'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }
}
