<?php

declare(strict_types=1);

namespace Modules\Document\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Document\Enums\AccessLevel;
use Modules\Document\Enums\DocumentStatus;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'folder_id' => ['nullable', 'string', 'exists:folders,id'],
            'status' => ['sometimes', 'string', 'in:'.implode(',', array_column(DocumentStatus::cases(), 'value'))],
            'access_level' => ['sometimes', 'string', 'in:'.implode(',', array_column(AccessLevel::cases(), 'value'))],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
        ];
    }
}
