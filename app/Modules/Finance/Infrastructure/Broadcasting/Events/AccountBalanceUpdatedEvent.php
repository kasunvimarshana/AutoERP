<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Broadcasting\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AccountBalanceUpdatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int    $tenantId,
        public readonly int    $accountId,
        public readonly string $accountCode,
        public readonly float  $previousBalance,
        public readonly float  $newBalance,
        public readonly string $side,
        public readonly float  $amount,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('tenant.' . $this->tenantId . '.finance')];
    }

    public function broadcastAs(): string
    {
        return 'account.balance.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'tenant_id'        => $this->tenantId,
            'account_id'       => $this->accountId,
            'account_code'     => $this->accountCode,
            'previous_balance' => $this->previousBalance,
            'new_balance'      => $this->newBalance,
            'side'             => $this->side,
            'amount'           => $this->amount,
        ];
    }
}
