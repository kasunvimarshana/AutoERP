<?php

declare(strict_types=1);

namespace Modules\Tenant\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertTenantDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $creating = $this->isMethod('post');
        $maxSize = max((int) config('tenant.documents.max_size_kb', 10240), 1);

        return [
            'expected_version' => $creating
                ? ['prohibited']
                : ['required', 'integer', 'min:1'],
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'document_type' => ['nullable', 'string', 'max:100'],
            'file' => [$creating ? 'required' : 'nullable', 'file', 'max:'.$maxSize],
        ];
    }
}
