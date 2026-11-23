<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Invoice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class UploadInvoiceBladeTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'template_file' => ['required', 'file', 'max:1024'], // max 1MB
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $file = $this->file('template_file');
            if ($file === null) {
                return;
            }

            $originalName = mb_strtolower($file->getClientOriginalName());
            if (! str_ends_with($originalName, '.blade.php')) {
                $v->errors()->add('template_file', 'The template file must be a valid Blade template with a .blade.php extension.');
            }
        });
    }
}
