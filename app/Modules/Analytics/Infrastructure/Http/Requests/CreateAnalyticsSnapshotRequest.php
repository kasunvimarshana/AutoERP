<?php

declare(strict_types=1);

namespace Modules\Analytics\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAnalyticsSnapshotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'org_unit_id' => ['nullable', 'integer', 'min:1'],
            'summary_date' => ['required', 'date_format:Y-m-d'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
