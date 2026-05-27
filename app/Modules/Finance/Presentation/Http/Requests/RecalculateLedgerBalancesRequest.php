<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RecalculateLedgerBalancesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'account_id' => ['nullable', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
