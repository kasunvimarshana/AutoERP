<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadUserAttachmentRequest extends FormRequest
{
    /**
     * Supported MIME extensions for user attachments.
     */
    private const ALLOWED_MIMES = 'jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,xls,xlsx,csv,txt,zip';

    /**
     * Maximum file size in kilobytes (20 MB).
     */
    private const MAX_FILE_SIZE_KB = 20480;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|max:'.self::MAX_FILE_SIZE_KB.'|mimes:'.self::ALLOWED_MIMES,
            'type' => 'nullable|string|max:50',
            'metadata' => 'nullable|array',
        ];
    }
}
