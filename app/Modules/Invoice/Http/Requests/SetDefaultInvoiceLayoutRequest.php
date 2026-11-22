<?php

declare(strict_types=1);

namespace Modules\Invoice\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SetDefaultInvoiceLayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:64'],
            'organization_unit_id' => ['nullable', 'integer'],
            'scope' => ['nullable', 'string', 'in:tenant,organization_unit'],
        ];
    }

    public function code(): string
    {
        return trim((string) $this->validated('code'));
    }

    public function organizationUnitId(): ?int
    {
        $value = $this->validated('organization_unit_id');

        return $value !== null ? (int) $value : null;
    }
}
