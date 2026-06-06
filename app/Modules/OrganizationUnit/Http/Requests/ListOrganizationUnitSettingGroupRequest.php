<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListOrganizationUnitSettingGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
