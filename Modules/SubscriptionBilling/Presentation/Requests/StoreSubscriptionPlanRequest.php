<?php

namespace Modules\SubscriptionBilling\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'code'          => ['required', 'string', 'max:64'],
            'description'   => ['nullable', 'string'],
            'billing_cycle' => ['required', 'string', 'in:monthly,quarterly,annually'],
            'price'         => ['required', 'numeric', 'min:0'],
            'trial_days'    => ['nullable', 'integer', 'min:0'],
            'is_active'     => ['nullable', 'boolean'],
        ];
    }
}
