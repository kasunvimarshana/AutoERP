<?php

declare(strict_types=1);

namespace Modules\Auth\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Services\Registration\RegistrationInvitationDeliveryService;
use Modules\Tenant\Queue\TenantAwareJobInterface;
use Modules\Tenant\Queue\TenantJobContext;

final class DeliverInitialAdministratorInvitation implements ShouldQueue, ShouldBeUniqueUntilProcessing, TenantAwareJobInterface
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;
    public int $uniqueFor = 600;

    /** @var list<int> */
    public array $backoff = [60, 300, 900, 3600];

    public function __construct(
        public readonly int $tenantId,
        public readonly int $deliveryId,
    ) {}

    public function uniqueId(): string
    {
        return $this->tenantId.':'.$this->deliveryId;
    }

    /** @return list<object> */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('auth-registration-delivery:'.$this->deliveryId))
                ->expireAfter(600)
                ->dontRelease(),
        ];
    }

    public function tenantJobContext(): TenantJobContext
    {
        return new TenantJobContext($this->tenantId);
    }

    public function handle(RegistrationInvitationDeliveryService $delivery): void
    {
        $delivery->deliver($this->tenantId, $this->deliveryId);
    }
}
