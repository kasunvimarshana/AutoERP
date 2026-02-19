<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Reporting\Enums\ReportFormat;
use Modules\Reporting\Enums\ReportStatus;
use Modules\Reporting\Enums\ReportType;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \Modules\Reporting\Models\Report::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', Rule::enum(ReportType::class)],
            'format' => ['required', Rule::enum(ReportFormat::class)],
            'status' => ['nullable', Rule::enum(ReportStatus::class)],
            'query_config' => ['required', 'array'],
            'query_config.table' => ['required', 'string'],
            'query_config.tenant_scoped' => ['nullable', 'boolean'],
            'query_config.organization_scoped' => ['nullable', 'boolean'],
            'query_config.joins' => ['nullable', 'array'],
            'fields' => ['required', 'array'],
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
