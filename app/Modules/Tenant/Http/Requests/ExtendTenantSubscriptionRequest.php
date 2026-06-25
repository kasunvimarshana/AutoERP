<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ExtendTenantSubscriptionRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'expected_tenant_version' => ['required', 'integer', 'min:1'],
            'expected_subscription_version' => ['required', 'integer', 'min:1'],
            'ends_at' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
