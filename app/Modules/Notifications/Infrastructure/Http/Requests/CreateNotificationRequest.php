<?php

declare(strict_types=1);

namespace Modules\Notifications\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Notifications\Domain\ValueObjects\EntityType;
use Modules\Notifications\Domain\ValueObjects\NotificationChannel;
use Modules\Notifications\Domain\ValueObjects\NotificationType;
use Modules\Notifications\Domain\ValueObjects\RecipientType;

class CreateNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $types     = implode(',', array_column(NotificationType::cases(), 'value'));
        $entities  = implode(',', array_column(EntityType::cases(), 'value'));
        $channels  = implode(',', array_column(NotificationChannel::cases(), 'value'));
        $recipients = implode(',', array_column(RecipientType::cases(), 'value'));

        return [
            'notification_number' => ['required', 'string', 'max:64'],
            'notification_type'   => ['required', 'string', "in:{$types}"],
            'entity_type'         => ['required', 'string', "in:{$entities}"],
            'entity_id'           => ['nullable', 'uuid'],
            'recipient_type'      => ['required', 'string', "in:{$recipients}"],
            'recipient_id'        => ['nullable', 'uuid'],
            'title'               => ['required', 'string', 'max:255'],
            'message'             => ['required', 'string'],
            'channel'             => ['required', 'string', "in:{$channels}"],
            'metadata'            => ['nullable', 'array'],
        ];
    }
}
