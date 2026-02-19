<?php

declare(strict_types=1);

namespace Modules\Notification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Notification\Enums\NotificationType;

class StoreTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \Modules\Notification\Models\NotificationTemplate::class);
    }

    public function rules(): array
    {
        return [
            'organization_id' => [
                'nullable',
                Rule::exists('organizations', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('notification_templates', 'code')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', Rule::enum(NotificationType::class)],
            'subject' => ['required', 'string', 'max:500'],
            'body_text' => ['required', 'string'],
            'body_html' => ['nullable', 'string'],
            'variables' => ['nullable', 'array'],
            'variables.*.name' => ['required', 'string', 'max:100'],
            'variables.*.type' => ['required', 'string', 'max:50'],
            'variables.*.required' => ['nullable', 'boolean'],
            'variables.*.default' => ['nullable'],
            'default_data' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'is_system' => ['nullable', 'boolean'],
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
