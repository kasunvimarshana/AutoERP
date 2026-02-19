<?php

declare(strict_types=1);

namespace Modules\Notification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Notification\Enums\NotificationType;

class UpdateTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $template = $this->route('template');

        return $this->user()->can('update', $template);
    }

    public function rules(): array
    {
        $template = $this->route('template');

        return [
            'organization_id' => [
                'nullable',
                Rule::exists('organizations', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('notification_templates', 'code')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->ignore($template->id)
                    ->whereNull('deleted_at'),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['sometimes', 'required', Rule::enum(NotificationType::class)],
            'subject' => ['sometimes', 'required', 'string', 'max:500'],
            'body_text' => ['sometimes', 'required', 'string'],
            'body_html' => ['nullable', 'string'],
            'variables' => ['nullable', 'array'],
            'variables.*.name' => ['required', 'string', 'max:100'],
            'variables.*.type' => ['required', 'string', 'max:50'],
            'variables.*.required' => ['nullable', 'boolean'],
            'variables.*.default' => ['nullable'],
            'default_data' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'organization_id' => 'organization',
            'body_text' => 'text body',
            'body_html' => 'HTML body',
            'variables.*.name' => 'variable name',
            'variables.*.type' => 'variable type',
        ];
    }
}
