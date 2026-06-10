<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Requests;

use Modules\Core\Http\Requests\TenantScopedRequest;

final class UpsertChequeTemplateRequest extends TenantScopedRequest
{
    public function rules(): array
    {
        $required = $this->isMethod('post') ? ['required'] : ['sometimes'];

        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
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
        ];
    }
}
