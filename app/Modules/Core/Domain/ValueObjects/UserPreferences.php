<?php

declare(strict_types=1);

namespace Modules\Core\Domain\ValueObjects;

class UserPreferences extends ValueObject
{
    private string $language;

    private string $timezone;

    private array $notifications;

    public function __construct(string $language = 'en', string $timezone = 'UTC', array $notifications = [])
    {
        $this->language = $language;
        $this->timezone = $timezone;
        $this->notifications = $notifications;
    }

    // Getters...
    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function getNotifications(): array
    {
        return $this->notifications;
    }

    public function toArray(): array
    {
        return [
            'language' => $this->language,
            'timezone' => $this->timezone,
            'notifications' => $this->notifications,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new static(
            $data['language'] ?? 'en',
            $data['timezone'] ?? 'UTC',
            $data['notifications'] ?? []
        );
    }
}
