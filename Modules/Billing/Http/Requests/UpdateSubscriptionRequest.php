<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Billing\Enums\SubscriptionStatus;

class UpdateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('id') ? \Modules\Billing\Models\Subscription::find($this->route('id')) : null);
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(SubscriptionStatus::class)],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
