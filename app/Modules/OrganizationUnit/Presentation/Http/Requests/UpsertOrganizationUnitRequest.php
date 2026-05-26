<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertOrganizationUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => $this->isMethod('post') ? ['required', 'integer', 'min:1'] : ['sometimes', 'integer', 'min:1'],
            'type_id' => ['nullable', 'integer', 'min:1'],
            'parent_id' => ['nullable', 'integer', 'min:1'],
            'name' => array_merge($required, ['string', 'max:255']),
            'code' => ['nullable', 'string', 'max:255'],
            'image_path' => ['nullable', 'string', 'max:2048'],
            'path' => ['nullable', 'string', 'max:1024'],
            'depth' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
            '_lft' => ['sometimes', 'integer'],
            '_rgt' => ['sometimes', 'integer'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}