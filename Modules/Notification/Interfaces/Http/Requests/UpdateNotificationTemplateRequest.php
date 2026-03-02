<?php

declare(strict_types=1);

namespace Modules\Notification\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Notification\Domain\Enums\NotificationChannel;

class UpdateNotificationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $channels = implode(',', array_column(NotificationChannel::cases(), 'value'));

        return [
            'tenant_id' => ['nullable', 'integer', 'min:1'],
            'channel' => ['nullable', 'string', 'in:'.$channels],
            'event_type' => ['nullable', 'string', 'max:100'],
            'name' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
