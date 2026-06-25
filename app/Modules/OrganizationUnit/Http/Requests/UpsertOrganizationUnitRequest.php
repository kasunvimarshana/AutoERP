<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class UpsertOrganizationUnitRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        $creating = $this->isMethod('post');
        return [
            'expected_version' => $creating ? ['prohibited'] : ['required', 'integer', 'min:1'],
            'type_id' => [$creating ? 'required' : 'sometimes', 'integer', 'min:1'],
            'parent_id' => [$creating ? 'required' : 'sometimes', 'integer', 'min:1'],
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'code' => $creating
                ? ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9][A-Za-z0-9_-]*$/']
                : ['prohibited'],
            'description' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'is_active' => ['prohibited'],
            'image_path' => ['prohibited'],
            'logo_object_key' => ['prohibited'],
        ];
    }
}
