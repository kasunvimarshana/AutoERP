<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadTenantAttachmentRequest extends FormRequest
{
    /**
     * Supported MIME types for tenant attachments.
     *
     * Extend this list as new attachment types become necessary.
     */
    private const ALLOWED_MIMES = 'jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,xls,xlsx,csv,txt,zip';

    /**
     * Maximum file size in kilobytes (20 MB per file).
     */
    private const MAX_FILE_SIZE_KB = 20480;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Single-file upload: file must be present.
        // Bulk upload: files[] array must be present.
        // The request is valid when EITHER field is supplied.
        return [
            // Single upload
            'file'                  => 'nullable|file|max:'.self::MAX_FILE_SIZE_KB.'|mimes:'.self::ALLOWED_MIMES,

            // Bulk upload
            'files'                 => 'nullable|array|min:1',
            'files.*'               => 'file|max:'.self::MAX_FILE_SIZE_KB.'|mimes:'.self::ALLOWED_MIMES,

            // Common optional fields
            'type'                  => 'nullable|string|max:50',
            'metadata'              => 'nullable|string|json',
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $v): void {
            $hasSingle = $this->hasFile('file');
            $hasBulk   = $this->hasFile('files');

            if (! $hasSingle && ! $hasBulk) {
                $v->errors()->add('file', 'You must provide either a single file or a files array.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'file.mimes'    => 'The file must be one of the supported types: '.self::ALLOWED_MIMES.'.',
            'file.max'      => 'The file must not exceed '.(self::MAX_FILE_SIZE_KB / 1024).' MB.',
            'files.*.mimes' => 'Each file must be one of the supported types: '.self::ALLOWED_MIMES.'.',
            'files.*.max'   => 'Each file must not exceed '.(self::MAX_FILE_SIZE_KB / 1024).' MB.',
        ];
    }
}
