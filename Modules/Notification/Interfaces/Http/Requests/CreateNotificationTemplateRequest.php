<?php

declare(strict_types=1);

namespace Modules\Notification\Interfaces\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Notification\Domain\Enums\NotificationChannel;

class CreateNotificationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $channels = implode(',', array_column(NotificationChannel::cases(), 'value'));

        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'channel' => ['required', 'string', 'in:'.$channels],
            'event_type' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
