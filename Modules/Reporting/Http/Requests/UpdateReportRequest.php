<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Reporting\Enums\ReportFormat;
use Modules\Reporting\Enums\ReportStatus;
use Modules\Reporting\Enums\ReportType;

class UpdateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('report'));
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['sometimes', Rule::enum(ReportType::class)],
            'format' => ['sometimes', Rule::enum(ReportFormat::class)],
            'status' => ['sometimes', Rule::enum(ReportStatus::class)],
            'query_config' => ['sometimes', 'array'],
            'fields' => ['sometimes', 'array'],
            'filters' => ['nullable', 'array'],
            'grouping' => ['nullable', 'array'],
            'sorting' => ['nullable', 'array'],
            'aggregations' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'is_template' => ['nullable', 'boolean'],
            'is_shared' => ['nullable', 'boolean'],
        ];
    }
}
