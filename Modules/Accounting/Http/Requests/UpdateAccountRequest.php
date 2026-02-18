<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Account Request
 *
 * Validates data for updating an existing account.
 */
class UpdateAccountRequest extends FormRequest
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
        $accountId = $this->route('id');

        return [
            'account_number' => ['nullable', 'string', 'max:50', "unique:accounts,account_number,{$accountId}"],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', 'required', 'in:asset,liability,equity,revenue,expense'],
            'sub_type' => ['nullable', 'string', 'max:100'],
            'parent_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'currency' => ['sometimes', 'required', 'string', 'size:3'],
            'is_active' => ['boolean'],
            'is_system' => ['boolean'],
            'allow_manual_entries' => ['boolean'],
            'balance' => ['nullable', 'numeric'],
            'tax_rate_id' => ['nullable', 'integer'],
            'tags' => ['nullable', 'array'],
        ];
    }
}
