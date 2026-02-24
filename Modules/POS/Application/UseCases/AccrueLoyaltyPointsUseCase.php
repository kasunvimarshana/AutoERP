<?php

namespace Modules\POS\Application\UseCases;

use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\POS\Domain\Contracts\LoyaltyProgramRepositoryInterface;
use Modules\POS\Domain\Events\LoyaltyPointsAccrued;
use Modules\Shared\Domain\Contracts\UseCaseInterface;

/**
 * Accrues loyalty points to a customer's card based on order amount.
 *
 * Points earned = floor(order_amount × points_per_currency_unit).
 * If the customer does not yet have a card for the program, one is created.
 */
class AccrueLoyaltyPointsUseCase implements UseCaseInterface
{
    public function __construct(
        private LoyaltyProgramRepositoryInterface $loyaltyRepo,
    ) {}

    public function execute(array $data): mixed
    {
        $programId   = $data['program_id'];
        $customerId  = $data['customer_id'];
        $tenantId    = $data['tenant_id'];
        $orderAmount = (string) ($data['order_amount'] ?? '0');

        if (bccomp($orderAmount, '0', 8) <= 0) {
            throw new DomainException('Order amount must be greater than zero to accrue points.');
        }

        $program = $this->loyaltyRepo->findById($programId);
        if (! $program) {
            throw new ModelNotFoundException('Loyalty program not found.');
        }

        if (! $program->is_active) {
            throw new DomainException('Cannot accrue points for an inactive loyalty program.');
        }

        return DB::transaction(function () use ($program, $customerId, $tenantId, $orderAmount, $data) {
            // Calculate points: floor(amount × rate)
            $pointsEarned = (string) (int) floor(
                (float) bcmul($orderAmount, (string) $program->points_per_currency_unit, 8)
            );

            if (bccomp($pointsEarned, '0', 8) <= 0) {
                throw new DomainException('Order amount is too small to earn any points.');
            }

            // Find or create the customer's loyalty card
            $card = $this->loyaltyRepo->findCardByCustomer($tenantId, $customerId, $program->id);
            if (! $card) {
                $card = $this->loyaltyRepo->createCard([
                    'id'             => (string) Str::uuid(),
                    'tenant_id'      => $tenantId,
                    'program_id'     => $program->id,
                    'customer_id'    => $customerId,
                    'points_balance' => '0',
                    'is_active'      => true,
                ]);
            }

            $newBalance = bcadd((string) $card->points_balance, $pointsEarned, 0);
            $card       = $this->loyaltyRepo->updateCard($card->id, ['points_balance' => $newBalance]);

            $this->loyaltyRepo->createTransaction([
                'id'          => (string) Str::uuid(),
                'tenant_id'   => $tenantId,
                'card_id'     => $card->id,
                'type'        => 'accrual',
                'points'      => $pointsEarned,
                'reference'   => $data['reference'] ?? null,
                'notes'       => "Accrued {$pointsEarned} points on order.",
            ]);

            Event::dispatch(new LoyaltyPointsAccrued(
                cardId:     $card->id,
                tenantId:   $tenantId,
                customerId: $customerId,
                pointsAdded: $pointsEarned,
                newBalance:  $newBalance,
            ));

            return $card;
        });
    }
}
