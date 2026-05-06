<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationUnitUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => 'sometimes|nullable|string|max:255',
            'is_primary' => 'sometimes|required|boolean',
        ];
    }
}
