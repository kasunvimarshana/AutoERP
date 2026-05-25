<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListOrganizationUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
        ];
    }
}