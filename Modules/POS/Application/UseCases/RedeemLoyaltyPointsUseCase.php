<?php

namespace Modules\POS\Application\UseCases;

use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\POS\Domain\Contracts\LoyaltyProgramRepositoryInterface;
use Modules\POS\Domain\Events\LoyaltyPointsRedeemed;
use Modules\Shared\Domain\Contracts\UseCaseInterface;

/**
 * Redeems loyalty points from a customer's card.
 *
 * Discount amount = floor(points_to_redeem / redemption_rate).
 * Guards: card must exist, be active, and have sufficient balance.
 */
class RedeemLoyaltyPointsUseCase implements UseCaseInterface
{
    public function __construct(
        private LoyaltyProgramRepositoryInterface $loyaltyRepo,
    ) {}

    public function execute(array $data): mixed
    {
        $cardId         = $data['card_id'];
        $pointsToRedeem = (string) ($data['points_to_redeem'] ?? '0');

        if (bccomp($pointsToRedeem, '0', 0) <= 0) {
            throw new DomainException('Points to redeem must be greater than zero.');
        }

        $card = $this->loyaltyRepo->findCardById($cardId);
        if (! $card) {
            throw new ModelNotFoundException('Loyalty card not found.');
        }

        if (! $card->is_active) {
            throw new DomainException('Cannot redeem points from an inactive loyalty card.');
        }

        if (bccomp((string) $card->points_balance, $pointsToRedeem, 0) < 0) {
            throw new DomainException('Insufficient points balance.');
        }

        $program = $this->loyaltyRepo->findById($card->program_id);
        if (! $program || ! $program->is_active) {
            throw new DomainException('The loyalty program associated with this card is not active.');
        }

        return DB::transaction(function () use ($card, $program, $pointsToRedeem, $data) {
            // Discount = floor(points / redemption_rate)
            $discountAmount = (string) floor(
                (float) bcdiv($pointsToRedeem, (string) $program->redemption_rate, 8)
            );

            $newBalance = bcsub((string) $card->points_balance, $pointsToRedeem, 0);
            $card       = $this->loyaltyRepo->updateCard($card->id, ['points_balance' => $newBalance]);

            $this->loyaltyRepo->createTransaction([
                'id'        => (string) Str::uuid(),
                'tenant_id' => $card->tenant_id,
                'card_id'   => $card->id,
                'type'      => 'redemption',
                'points'    => '-' . $pointsToRedeem,
                'reference' => $data['reference'] ?? null,
                'notes'     => "Redeemed {$pointsToRedeem} points for {$discountAmount} discount.",
            ]);

            Event::dispatch(new LoyaltyPointsRedeemed(
                cardId:         $card->id,
                tenantId:       $card->tenant_id,
                customerId:     $card->customer_id,
                pointsRedeemed: $pointsToRedeem,
                discountAmount: $discountAmount,
                newBalance:     $newBalance,
            ));

            return ['card' => $card, 'discount_amount' => $discountAmount];
        });
    }
}
