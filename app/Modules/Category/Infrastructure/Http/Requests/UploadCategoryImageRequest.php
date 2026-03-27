<?php

declare(strict_types=1);

namespace Modules\Category\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadCategoryImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file'     => 'required|file|mimes:jpg,jpeg,png,gif,webp,svg|max:10240',
            'metadata' => 'nullable|string',
        ];
    }
}
