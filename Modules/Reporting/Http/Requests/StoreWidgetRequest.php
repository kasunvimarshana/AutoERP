<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Reporting\Enums\ChartType;
use Modules\Reporting\Enums\WidgetType;

class StoreWidgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('dashboard'));
    }

    public function rules(): array
    {
        return [
            'dashboard_id' => ['required', 'integer', 'exists:dashboards,id'],
            'report_id' => ['nullable', 'integer', 'exists:reports,id'],
            'type' => ['required', Rule::enum(WidgetType::class)],
            'chart_type' => ['nullable', Rule::enum(ChartType::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'configuration' => ['nullable', 'array'],
            'data_source' => ['nullable', 'array'],
            'refresh_interval' => ['nullable', 'integer', 'min:10'],
            'width' => ['nullable', 'integer', 'min:1', 'max:12'],
            'height' => ['nullable', 'integer', 'min:1', 'max:12'],
            'position_x' => ['nullable', 'integer', 'min:0'],
            'position_y' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
