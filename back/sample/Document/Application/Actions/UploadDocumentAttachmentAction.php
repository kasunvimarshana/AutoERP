<?php

namespace Modules\Document\Application\Actions;

use Illuminate\Http\UploadedFile;

class UploadDocumentAttachmentAction
{
    public function execute(UploadedFile $file): array
    {
        return [
            'disk' => config('document.attachments.disk'),
            'directory' => config('document.attachments.directory'),
            'file_name' => $file->getClientOriginalName(),
            'stored_name' => $file->hashName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'uploaded_by' => null,
            'uploaded_file' => $file,
        ];
    }
}
