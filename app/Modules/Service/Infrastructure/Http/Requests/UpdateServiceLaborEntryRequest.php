<?php

declare(strict_types=1);

namespace Modules\Service\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceLaborEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'started_at' => ['sometimes', 'nullable', 'date'],
            'ended_at' => ['sometimes', 'nullable', 'date'],
            'hours_worked' => ['sometimes', 'numeric', 'min:0'],
            'labor_rate' => ['sometimes', 'numeric', 'min:0'],
            'labor_amount' => ['sometimes', 'numeric', 'min:0'],
            'commission_rate' => ['sometimes', 'numeric', 'min:0'],
            'commission_amount' => ['sometimes', 'numeric', 'min:0'],
            'incentive_amount' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', 'string', 'in:draft,approved,posted'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
