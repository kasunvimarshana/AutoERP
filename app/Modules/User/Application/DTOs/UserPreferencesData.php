<?php

namespace Modules\User\Application\DTOs;

use Modules\Core\Application\DTOs\BaseDto;

class UserPreferencesData extends BaseDto
{
    public ?string $language;
    public ?string $timezone;
    public ?array $notifications;

    public function rules(): array
    {
        return [
            'language'     => 'nullable|string|in:en,es,fr,de',
            'timezone'     => 'nullable|string|timezone',
            'notifications'=> 'nullable|array',
        ];
    }
}
