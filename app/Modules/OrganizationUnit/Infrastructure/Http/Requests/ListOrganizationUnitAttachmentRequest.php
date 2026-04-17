<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListOrganizationUnitAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'nullable|string|max:50',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ];
    }
}
