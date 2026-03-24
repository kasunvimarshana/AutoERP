<?php

namespace Modules\User\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadUserAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file'     => 'required|file|max:10240',
            'type'     => 'nullable|string|max:50',
            'metadata' => 'nullable|array',
        ];
    }
}
