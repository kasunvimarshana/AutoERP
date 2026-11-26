<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payment\Http\Requests;

use Illuminate\Validation\Validator;
use Modules\Core\Http\Requests\TenantScopedRequest;

final class UpsertChequeTemplateRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];
        $versionRules = $this->isMethod('post') ? ['nullable', 'integer', 'min:1'] : ['required', 'integer', 'min:1'];

        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'expected_version' => $versionRules,
            'bank_name' => ['nullable', 'string', 'max:255'],
            'template_name' => array_merge($required, ['string', 'max:255']),
            'page_width_mm' => array_merge($required, ['numeric', 'gt:0', 'max:1000']),
            'page_height_mm' => array_merge($required, ['numeric', 'gt:0', 'max:1000']),
            'date_x_mm' => array_merge($required, ['numeric', 'min:0', 'max:1000']),
            'date_y_mm' => array_merge($required, ['numeric', 'min:0', 'max:1000']),
            'payee_x_mm' => array_merge($required, ['numeric', 'min:0', 'max:1000']),
            'payee_y_mm' => array_merge($required, ['numeric', 'min:0', 'max:1000']),
            'amount_x_mm' => array_merge($required, ['numeric', 'min:0', 'max:1000']),
            'amount_y_mm' => array_merge($required, ['numeric', 'min:0', 'max:1000']),
            'amount_words_x_mm' => array_merge($required, ['numeric', 'min:0', 'max:1000']),
            'amount_words_y_mm' => array_merge($required, ['numeric', 'min:0', 'max:1000']),
            'cheque_number_x_mm' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'cheque_number_y_mm' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'font_size' => array_merge($required, ['numeric', 'between:6,72']),
            'font_family' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
            'metadata.date_format' => ['nullable', 'string', 'max:50'],
            'metadata.date_letter_spacing' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'metadata.crossing_type' => ['nullable', 'string', 'in:none,ac_payee,not_negotiable'],
            'metadata.crossing_x_mm' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'metadata.crossing_y_mm' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'metadata.bearer_strike_through' => ['nullable', 'boolean'],
            'metadata.bearer_x_mm' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'metadata.bearer_y_mm' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'metadata.amount_words_line_2_x_mm' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'metadata.amount_words_line_2_y_mm' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'metadata.amount_prefix' => ['nullable', 'string', 'max:20'],
            'metadata.amount_suffix' => ['nullable', 'string', 'max:20'],
            'metadata.currency_code' => ['nullable', 'string', 'max:10'],
            'metadata.memo_x_mm' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'metadata.memo_y_mm' => ['nullable', 'numeric', 'min:0', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $width = $this->filled('page_width_mm') ? (float) $this->input('page_width_mm') : null;
            $height = $this->filled('page_height_mm') ? (float) $this->input('page_height_mm') : null;

            if ($width !== null) {
                $xFields = ['date_x_mm', 'payee_x_mm', 'amount_x_mm', 'amount_words_x_mm', 'cheque_number_x_mm'];
                foreach ($xFields as $field) {
                    if ($this->filled($field) && (float) $this->input($field) > $width) {
                        $v->errors()->add($field, sprintf('The %s position cannot exceed the page width (%.2f mm).', $field, $width));
                    }
                }
            }

            if ($height !== null) {
                $yFields = ['date_y_mm', 'payee_y_mm', 'amount_y_mm', 'amount_words_y_mm', 'cheque_number_y_mm'];
                foreach ($yFields as $field) {
                    if ($this->filled($field) && (float) $this->input($field) > $height) {
                        $v->errors()->add($field, sprintf('The %s position cannot exceed the page height (%.2f mm).', $field, $height));
                    }
                }
            }
        });
    }
}
