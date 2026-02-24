<?php

namespace Modules\Notification\Application\UseCases;

use Modules\Notification\Infrastructure\Jobs\SendNotificationJob;
use Modules\Shared\Domain\Contracts\UseCaseInterface;

/**
 * Orchestrates tenant-scoped notification dispatch.
 *
 * Pushes a SendNotificationJob onto the queue so that the calling
 * thread is never blocked by slow channel adapters (email, SMS, push).
 */
class SendNotificationUseCase implements UseCaseInterface
{
    /**
     * @param array{
     *   tenant_id: string,
     *   user_id: string,
     *   type: string,
     *   channel: string,
     *   data: array<string, mixed>,
     * } $data
     */
    public function execute(array $data): mixed
    {
        SendNotificationJob::dispatch(
            tenantId: $data['tenant_id'],
            userId: $data['user_id'],
            type: $data['type'],
            channel: $data['channel'],
            data: $data['data'] ?? [],
        );

        return null;
    }
}
