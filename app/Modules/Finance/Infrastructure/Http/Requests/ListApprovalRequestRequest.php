<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListApprovalRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tenant_id' => ['sometimes', 'integer'],
            'entity_type' => ['sometimes', 'string'],
            'entity_id' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'in:pending,approved,rejected,cancelled'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'sort' => ['sometimes', 'string'],
        ];
    }
}
