<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadOrganizationUnitAttachmentRequest extends FormRequest
{
    private const ALLOWED_MIMES = 'jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,xls,xlsx,csv,txt,zip';

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
