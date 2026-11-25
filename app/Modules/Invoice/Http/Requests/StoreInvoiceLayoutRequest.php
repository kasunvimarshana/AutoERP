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
use Illuminate\Validation\Rule;
use Modules\Invoice\DTOs\InvoiceLayoutData;
use Modules\Invoice\Enums\InvoiceLayoutTemplate;
use Modules\Invoice\Enums\InvoiceLayoutType;
use Modules\Invoice\Enums\InvoiceOrientation;
use Modules\Invoice\Enums\InvoicePaperSize;
use Modules\Invoice\Validators\InvoiceCssSanitizer;

final class StoreInvoiceLayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_\-]+$/'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'paper_size' => ['required', 'string', 'in:'.implode(',', InvoicePaperSize::values())],
            'orientation' => ['required', 'string', 'in:'.implode(',', InvoiceOrientation::values())],
            'template_type' => ['nullable', 'string', 'in:'.implode(',', InvoiceLayoutType::values())],
            'view_template' => [
                Rule::requiredIf(fn (): bool => ($this->input('template_type') ?? InvoiceLayoutType::Preset->value) === InvoiceLayoutType::Preset->value),
                'nullable',
                'string',
                'in:'.implode(',', InvoiceLayoutTemplate::values()),
            ],
            'blade_content' => [
                Rule::requiredIf(fn (): bool => $this->input('template_type') === InvoiceLayoutType::Blade->value),
                'nullable',
                'string',
                'max:2000000',
            ],
            'is_active' => ['nullable', 'boolean'],
            'custom_css' => [
                'nullable',
                'string',
                'max:50000',
                static function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_string($value) && InvoiceCssSanitizer::containsDangerousConstructs($value)) {
                        $fail('The custom CSS contains forbidden HTML tags or unsafe script constructs.');
                    }
                },
            ],
            'header_content' => ['nullable', 'string', 'max:10000'],
            'footer_content' => ['nullable', 'string', 'max:10000'],
            'settings' => ['nullable', 'array'],
            'settings.primary_color' => ['nullable', 'string', 'max:32'],
            'settings.font_family' => ['nullable', 'string', 'in:serif,sans-serif,monospace'],
            'settings.show_company_logo' => ['nullable', 'boolean'],
            'settings.show_tax_summary' => ['nullable', 'boolean'],
            'settings.show_payment_details' => ['nullable', 'boolean'],
            'settings.show_signatures' => ['nullable', 'boolean'],
            'settings.show_amount_in_words' => ['nullable', 'boolean'],
            'settings.show_notes' => ['nullable', 'boolean'],
        ];
    }

    public function toDto(): InvoiceLayoutData
    {
        return InvoiceLayoutData::fromArray($this->validated());
    }
}
