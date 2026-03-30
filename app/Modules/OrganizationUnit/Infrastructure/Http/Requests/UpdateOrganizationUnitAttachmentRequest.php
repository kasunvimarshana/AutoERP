<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates PATCH /org-units/{unit}/attachments/{attachment} requests.
 *
 * Only the classification fields of an attachment (type and metadata) may be
 * updated without replacing the underlying file.  At least one of the two
 * fields must be present in the payload.
 */
class UpdateOrganizationUnitAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'     => 'sometimes|nullable|string|max:50',
            'metadata' => 'sometimes|nullable|array',
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $v): void {
            $hasType     = $this->has('type');
            $hasMetadata = $this->has('metadata');

            if (! $hasType && ! $hasMetadata) {
                $v->errors()->add('type', 'At least one of type or metadata must be provided.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'type.max'  => 'The type must not exceed 50 characters.',
            'metadata.array' => 'The metadata must be a valid JSON object (array).',
        ];
    }
}
