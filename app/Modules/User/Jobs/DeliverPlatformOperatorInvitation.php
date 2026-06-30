<?php

declare(strict_types=1);

namespace Modules\User\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Modules\User\Services\Platform\Invitations\PlatformOperatorInvitationDeliveryService;

final class DeliverPlatformOperatorInvitation implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private const OVERLAP_RELEASE_SECONDS = 5;
    private const OVERLAP_EXPIRY_SECONDS = 600;

    public int $tries = 5;
    public int $uniqueFor = 600;

    /** @var list<int> */
    public array $backoff = [60, 300, 900, 3600];

    public function __construct(
        public readonly int $invitationId,
        public readonly int $deliveryId,
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->deliveryId;
    }

    /** @return list<object> */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('platform-operator-invitation:'.$this->invitationId))
                ->releaseAfter(self::OVERLAP_RELEASE_SECONDS)
                ->expireAfter(self::OVERLAP_EXPIRY_SECONDS),
        ];
    }

    public function handle(PlatformOperatorInvitationDeliveryService $delivery): void
    {
        $delivery->deliver($this->deliveryId);
    }
}
