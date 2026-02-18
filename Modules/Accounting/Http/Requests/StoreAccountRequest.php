<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Accounting\Entities\Account;

/**
 * Store Account Request
 *
 * Validates data for creating a new account.
 */
class StoreAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'account_number' => ['nullable', 'string', 'max:50', 'unique:accounts,account_number'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:asset,liability,equity,revenue,expense'],
            'sub_type' => ['nullable', 'string', 'max:100'],
            'parent_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'currency' => ['required', 'string', 'size:3'],
            'is_active' => ['boolean'],
            'is_system' => ['boolean'],
            'allow_manual_entries' => ['boolean'],
            'balance' => ['nullable', 'numeric'],
            'tax_rate_id' => ['nullable', 'integer'],
            'tags' => ['nullable', 'array'],
        ];
    }
}
