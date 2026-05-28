<?php

declare(strict_types=1);

namespace Modules\Voucher\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertVoucherTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $id = $this->route('type');

        return [
            'tenant_id' => [$id === null ? 'required' : 'sometimes', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'name' => [$id === null ? 'required' : 'sometimes', 'string', 'max:255'],
            'code' => [$id === null ? 'required' : 'sometimes', 'string', 'max:120'],
            'direction' => ['nullable', 'string', 'max:40'],
            'posting_behavior' => ['nullable', 'string', 'max:60'],
            'requires_approval' => ['nullable', 'boolean'],
            'allow_direct_posting' => ['nullable', 'boolean'],
            'allow_reversal' => ['nullable', 'boolean'],
            'allow_partial_allocation' => ['nullable', 'boolean'],
            'document_type_id' => ['nullable', 'integer', 'min:1'],
            'document_definition_id' => ['nullable', 'integer', 'min:1'],
            'allowed_payment_methods' => ['nullable', 'array'],
            'status_workflow' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
