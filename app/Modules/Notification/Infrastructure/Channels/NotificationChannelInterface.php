<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Channels;

use Modules\Notification\Domain\Entities\Notification;

/**
 * Contract for a single delivery-channel adapter.
 *
 * Implementations are registered in NotificationChannelDispatcher by channel name.
 */
interface NotificationChannelInterface
{
    /**
     * Deliver the notification through this channel.
     *
     * @throws \RuntimeException when delivery fails.
     */
    public function send(Notification $notification): void;
}
