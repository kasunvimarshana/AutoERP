<?php

declare(strict_types=1);

namespace Modules\Finance\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CloseFiscalPeriodRequest extends FormRequest
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
            'closed_by' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
