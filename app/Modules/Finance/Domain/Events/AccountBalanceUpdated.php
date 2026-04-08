<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Events;

use Modules\Core\Domain\Events\BaseEvent;

final class AccountBalanceUpdated extends BaseEvent
{
    public function __construct(
        int $tenantId,
        public readonly int    $accountId,
        public readonly string $accountCode,
        public readonly float  $previousBalance,
        public readonly float  $newBalance,
        public readonly string $side,
        public readonly float  $amount,
        ?int $orgUnitId = null,
    ) {
        parent::__construct($tenantId, $orgUnitId);
    }

    public function broadcastAs(): string
    {
        return 'account.balance.updated';
    }

    public function broadcastWith(): array
    {
        return array_merge(parent::broadcastWith(), [
            'account_id'       => $this->accountId,
            'account_code'     => $this->accountCode,
            'previous_balance' => $this->previousBalance,
            'new_balance'      => $this->newBalance,
            'side'             => $this->side,
            'amount'           => $this->amount,
        ]);
    }
}
