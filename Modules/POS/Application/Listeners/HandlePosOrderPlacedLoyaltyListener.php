<?php

namespace Modules\POS\Application\Listeners;

use Modules\POS\Application\UseCases\AccrueLoyaltyPointsUseCase;
use Modules\POS\Domain\Contracts\LoyaltyProgramRepositoryInterface;
use Modules\POS\Domain\Events\PosOrderPlaced;


class HandlePosOrderPlacedLoyaltyListener
{
    public function __construct(
        private LoyaltyProgramRepositoryInterface $loyaltyRepo,
        private AccrueLoyaltyPointsUseCase $accrueUseCase,
    ) {}

    public function handle(PosOrderPlaced $event): void
    {
        if ($event->tenantId === '' || $event->customerId === null) {
            return;
        }

        if (bccomp($event->totalAmount, '0', 8) <= 0) {
            return;
        }

        $program = $this->loyaltyRepo->findActiveByTenant($event->tenantId);
        if ($program === null) {
            return;
        }

        try {
            $this->accrueUseCase->execute([
                'tenant_id'    => $event->tenantId,
                'program_id'   => $program->id,
                'customer_id'  => $event->customerId,
                'order_amount' => $event->totalAmount,
                'reference'    => $event->orderId,
            ]);
        } catch (\Throwable) {
            // Graceful degradation: loyalty accrual failure must not affect
            // the already-committed POS order.
        }
    }
}
