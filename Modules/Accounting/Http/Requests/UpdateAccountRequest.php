<?php

declare(strict_types=1);

namespace Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Accounting\Enums\AccountStatus;
use Modules\Accounting\Enums\AccountType;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('account'));
    }

    public function rules(): array
    {
        $accountId = $this->route('account')->id ?? null;

        return [
            'organization_id' => [
                'nullable',
                Rule::exists('organizations', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'parent_id' => [
                'nullable',
                Rule::exists('accounts', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('accounts', 'code')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->ignore($accountId)
                    ->whereNull('deleted_at'),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['sometimes', 'required', Rule::enum(AccountType::class)],
            'status' => ['nullable', Rule::enum(AccountStatus::class)],
            'normal_balance' => ['sometimes', 'required', 'in:debit,credit'],
            'is_bank_account' => ['nullable', 'boolean'],
            'is_reconcilable' => ['nullable', 'boolean'],
            'allow_manual_entries' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'organization_id' => 'organization',
            'parent_id' => 'parent account',
            'code' => 'account code',
            'name' => 'account name',
            'description' => 'description',
            'type' => 'account type',
            'status' => 'status',
            'normal_balance' => 'normal balance',
        ];
    }
}
