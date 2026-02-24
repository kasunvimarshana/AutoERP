<?php

namespace Modules\SubscriptionBilling\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id'         => ['required', 'uuid'],
            'subscriber_type' => ['required', 'string', 'max:64'],
            'subscriber_id'   => ['required', 'uuid'],
        ];
    }
}
