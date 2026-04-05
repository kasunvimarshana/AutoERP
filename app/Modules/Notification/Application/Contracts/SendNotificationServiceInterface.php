<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Contracts;

use Modules\Notification\Domain\Entities\Notification;

interface SendNotificationServiceInterface
{
    /**
     * Create and dispatch a notification to a user.
     *
     * If a matching active template exists for the type + channel, it will
     * be rendered using the supplied variables.  Otherwise the provided
     * title and body are used directly.
     *
     * The notification is persisted first (status=pending) and then the
     * channel dispatcher is invoked.  On success the status transitions to
     * "sent"; on failure it transitions to "failed".
     *
     * @param array<string, mixed>  $variables   Template placeholder values.
     * @param array<string, mixed>  $data        Arbitrary context data attached to the record.
     */
    public function send(
        int    $tenantId,
        int    $userId,
        string $type,
        string $channel,
        string $title,
        string $body,
        array  $variables = [],
        array  $data      = [],
    ): Notification;
}
