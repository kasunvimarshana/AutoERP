<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadTenantAttachmentRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()?->can('uploadAttachment', Tenant::class) ?? false;
    }

    public function rules()
    {
        return [
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx',
            'type' => 'nullable|string|max:50',
            'metadata' => 'nullable|array',
        ];
    }
}
