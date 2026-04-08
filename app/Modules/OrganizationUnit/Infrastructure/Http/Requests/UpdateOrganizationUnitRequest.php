<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'sometimes|required|string|max:255',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'metadata'    => 'nullable|array',
            'parent_id'   => 'nullable|integer|exists:organization_units,id',
        ];
    }
}
