<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Single-file upload (optional when files[] is provided)
            'file'        => 'nullable|file|image|max:10240',
            'sort_order'  => 'nullable|integer|min:0',
            'is_primary'  => 'nullable|boolean',
            'metadata'    => 'nullable|string',

            // Bulk upload (optional when file is provided)
            'files'       => 'nullable|array|min:1',
            'files.*'     => 'file|image|max:10240',

            // Bulk-specific options
            'sort_order_start'  => 'nullable|integer|min:0',
            'is_primary_index'  => 'nullable|integer|min:0',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v): void {
            $hasFile  = $this->hasFile('file');
            $hasFiles = $this->hasFile('files');

            if (! $hasFile && ! $hasFiles) {
                $v->errors()->add('file', 'Either file or files[] must be provided.');
            }
        });
    }
}

