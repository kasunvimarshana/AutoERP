<?php

namespace Modules\OrganizationUnit\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveOrganizationUnitRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()?->can('move', OrganizationUnit::class) ?? false;
    }

    public function rules()
    {
        return [
            'parent_id' => 'nullable|integer|exists:organization_units,id',
        ];
    }
}
