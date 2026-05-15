<?php

namespace Modules\Document\Presentation\API\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentAttachmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:'.config('document.attachments.max_size_kb'),
                'mimetypes:'.implode(',', config('document.attachments.allowed_mime_types')),
            ],
        ];
    }
}
