<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApprovalRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'workflow_config_id' => ['required', 'integer', 'exists:approval_workflow_configs,id'],
            'entity_type' => ['required', 'string', 'max:255'],
            'entity_id' => ['required', 'integer'],
            'requested_by_user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
