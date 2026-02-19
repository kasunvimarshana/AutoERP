<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Reporting\Enums\ChartType;
use Modules\Reporting\Enums\WidgetType;

class UpdateWidgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('widget'));
    }

    public function rules(): array
    {
        return [
            'report_id' => ['sometimes', 'integer', 'exists:reports,id'],
            'type' => ['sometimes', Rule::enum(WidgetType::class)],
            'chart_type' => ['nullable', Rule::enum(ChartType::class)],
            'title' => ['sometimes', 'string', 'max:255'],
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
