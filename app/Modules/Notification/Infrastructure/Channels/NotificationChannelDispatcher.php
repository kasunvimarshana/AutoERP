<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Channels;

use Modules\Notification\Domain\Entities\Notification;
use Modules\Notification\Domain\ValueObjects\NotificationChannel;

/**
 * Routes a notification to the correct channel adapter.
 *
 * Additional channels can be registered at boot time via addDriver(), making
 * the dispatcher fully extensible without modifying existing code (Open/Closed
 * principle).
 */
class NotificationChannelDispatcher
{
    /** @var array<string, NotificationChannelInterface> */
    private array $drivers = [];

    public function addDriver(string $channel, NotificationChannelInterface $driver): void
    {
        $this->drivers[$channel] = $driver;
    }

    /**
     * Dispatch the notification to its channel driver.
     *
     * @throws \RuntimeException when no driver is registered for the channel.
     */
    public function dispatch(Notification $notification): void
    {
        $channel = $notification->getChannel()->getValue();

        if (!isset($this->drivers[$channel])) {
            throw new \RuntimeException("No driver registered for notification channel: {$channel}");
        }

        $this->drivers[$channel]->send($notification);
    }

    public function hasDriver(string $channel): bool
    {
        return isset($this->drivers[$channel]);
    }
}
