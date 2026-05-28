<?php

namespace Modules\Document\Presentation\API\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                sprintf('max:%s', config('document.attachments.max_size_kb')),
                sprintf('mimetypes:%s', implode(',', config('document.attachments.allowed_mime_types'))),
            ],
        ];
    }
}
