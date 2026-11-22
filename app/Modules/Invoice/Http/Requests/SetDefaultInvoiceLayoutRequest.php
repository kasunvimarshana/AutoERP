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
            'scope' => ['nullable', 'string', 'in:tenant,organization_unit,printer'],
            'printer' => ['required_if:scope,printer', 'nullable', 'string', 'max:128', 'regex:/^[a-zA-Z0-9_\-\.]+$/'],
        ];
    }

    public function code(): string
    {
        return trim((string) $this->validated('code'));
    }

    public function scope(): string
    {
        return (string) ($this->validated('scope') ?? ($this->organizationUnitId() !== null ? 'organization_unit' : 'tenant'));
    }

    public function printer(): ?string
    {
        $value = $this->validated('printer');

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    public function organizationUnitId(): ?int
    {
        $value = $this->validated('organization_unit_id');

        return $value !== null ? (int) $value : null;
    }
}
