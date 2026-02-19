<?php

declare(strict_types=1);

namespace Modules\CRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\CRM\Enums\OpportunityStage;

class UpdateOpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        $opportunity = $this->route('opportunity');

        return $this->user()->can('update', $opportunity);
    }

    public function rules(): array
    {
        $opportunityId = $this->route('opportunity')->id;

        return [
            'customer_id' => [
                'nullable',
                Rule::exists('customers', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'organization_id' => [
                'nullable',
                Rule::exists('organizations', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'opportunity_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('opportunities', 'opportunity_code')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->ignore($opportunityId)
                    ->whereNull('deleted_at'),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'stage' => ['nullable', Rule::enum(OpportunityStage::class)],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'expected_close_date' => ['nullable', 'date'],
            'assigned_to_user_id' => [
                'nullable',
                Rule::exists('users', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'customer',
            'organization_id' => 'organization',
            'opportunity_code' => 'opportunity code',
            'expected_close_date' => 'expected close date',
            'assigned_to_user_id' => 'assigned to',
        ];
    }
}
