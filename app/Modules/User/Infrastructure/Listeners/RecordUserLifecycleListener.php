<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\User\Application\Events\UserCreated;

final class RecordUserLifecycleListener
{
    public function handle(UserCreated $event): void
    {
        Log::info('User created.', [
            'user_id' => $event->userId,
            'tenant_id' => $event->tenantId,
            'email' => $event->email,
        ]);
    }
}
