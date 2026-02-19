<?php

declare(strict_types=1);

namespace Modules\Notification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Notification\Enums\NotificationPriority;
use Modules\Notification\Enums\NotificationType;

class SendNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('notifications.create');
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                Rule::exists('users', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'organization_id' => [
                'nullable',
                Rule::exists('organizations', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'template_id' => [
                'nullable',
                Rule::exists('notification_templates', 'id')
                    ->where('tenant_id', $this->user()->currentTenant()->id)
                    ->whereNull('deleted_at'),
            ],
            'type' => ['required', Rule::enum(NotificationType::class)],
            'channel' => ['required', 'string', 'max:50'],
            'priority' => ['nullable', Rule::enum(NotificationPriority::class)],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'data' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'recipient user',
            'template_id' => 'notification template',
            'organization_id' => 'organization',
        ];
    }
}
