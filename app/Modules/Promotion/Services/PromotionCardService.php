<?php

declare(strict_types=1);

namespace Modules\Promotion\Services;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Promotion\Enums\PromotionBenefitKind;
use Modules\Promotion\Enums\PromotionCardAction;
use Modules\Promotion\Enums\PromotionCountScope;
use Modules\Promotion\Enums\PromotionDiscountType;
use Modules\Promotion\Enums\PromotionStatus;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class PromotionCardService
{
    private const RECENT_CARD_LIMIT = 100;

    private const GENERATED_CARD_PREFIX = 'CARD-';

    public function __construct(
        private readonly PromotionOfferService $offers,
        private readonly DecimalMath $math,
    ) {}

    public function templates(int $tenantId, ?int $organizationUnitId): array
    {
        $templates = DB::table('promotion_card_templates')->where('tenant_id', $tenantId)
            ->where(function ($query) use ($organizationUnitId): void {
                $query->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId);
            })->orderByDesc('id')->get();

        return $templates->map(function ($template) use ($tenantId): array {
            $row = (array) $template;
            $row['benefits'] = DB::table('promotion_card_benefits as benefit')
                ->leftJoin('items as item', 'item.id', '=', 'benefit.item_id')
                ->leftJoin('promotion_offers as offer', 'offer.id', '=', 'benefit.offer_id')
                ->where('benefit.tenant_id', $tenantId)->where('benefit.template_id', $template->id)
                ->orderBy('benefit.position')
                ->get(['benefit.*', 'item.name as item_name', 'offer.name as offer_name'])
                ->map(static function ($benefit): array {
                    $row = (array) $benefit;
                    $row['item'] = $benefit->item_id === null ? null : ['id' => (int) $benefit->item_id, 'name' => $benefit->item_name];
                    $row['offer'] = $benefit->offer_id === null ? null : ['id' => (int) $benefit->offer_id, 'name' => $benefit->offer_name];

                    return $row;
                })->all();

            return $row;
        })->all();
    }

    public function createTemplate(int $tenantId, ?int $organizationUnitId, int $actorId, array $input): array
    {
        if (trim((string) $input['code']) === '' || trim((string) $input['name']) === ''
            || $input['starts_on'] > $input['ends_on'] || $input['benefits'] === []) {
            throw new InvalidArgumentException('Enter a card name, valid period, and at least one benefit.');
        }
        $offerIds = [];
        foreach ($input['benefits'] as $benefit) {
            if ($benefit['kind'] === PromotionBenefitKind::Direct->value) {
                $discountType = PromotionDiscountType::tryFrom((string) $benefit['discount_type']);
                if ($discountType === null || (int) $benefit['use_limit'] < 1
                    || ($discountType === PromotionDiscountType::Percentage
                        && (! isset($benefit['discount_rate']) || $this->math->compare((string) $benefit['discount_rate'], '0') <= 0
                            || $this->math->compare((string) $benefit['discount_rate'], '100') > 0))) {
                    throw new InvalidArgumentException('Enter a valid direct benefit and use limit.');
                }
                $this->offers->jobItem($tenantId, $organizationUnitId, (int) $benefit['item_id']);
            } elseif ($benefit['kind'] === PromotionBenefitKind::Earned->value) {
                if (in_array((int) $benefit['offer_id'], $offerIds, true)) {
                    throw new InvalidArgumentException('Add each count offer only once per card.');
                }
                $offerIds[] = (int) $benefit['offer_id'];
                $offer = $this->offers->find($tenantId, (int) $benefit['offer_id']);
                $this->offers->assertBranch($offer['organization_unit_id'], $organizationUnitId);
                if ($offer['count_scope'] !== PromotionCountScope::Vehicle->value) {
                    throw new InvalidArgumentException('Card count benefits require a vehicle-based offer.');
                }
            } else {
                throw new InvalidArgumentException('Choose a supported card benefit type.');
            }
        }

        try {
            $templateId = DB::transaction(function () use ($tenantId, $organizationUnitId, $actorId, $input): int {
                $templateId = DB::table('promotion_card_templates')->insertGetId([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'code' => Str::upper(trim((string) $input['code'])),
                    'name' => trim((string) $input['name']),
                    'starts_on' => $input['starts_on'],
                    'ends_on' => $input['ends_on'],
                    'status' => PromotionStatus::Active->value,
                    'row_version' => 1,
                    'created_by' => $actorId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                foreach ($input['benefits'] as $position => $benefit) {
                    DB::table('promotion_card_benefits')->insert([
                        'tenant_id' => $tenantId,
                        'template_id' => $templateId,
                        'position' => $position + 1,
                        'kind' => $benefit['kind'],
                        'item_id' => $benefit['kind'] === PromotionBenefitKind::Direct->value ? $benefit['item_id'] : null,
                        'discount_type' => $benefit['kind'] === PromotionBenefitKind::Direct->value ? $benefit['discount_type'] : null,
                        'discount_rate' => $benefit['kind'] === PromotionBenefitKind::Direct->value
                            && $benefit['discount_type'] === PromotionDiscountType::Percentage->value
                                ? $benefit['discount_rate'] : null,
                        'use_limit' => $benefit['kind'] === PromotionBenefitKind::Direct->value ? $benefit['use_limit'] : null,
                        'offer_id' => $benefit['kind'] === PromotionBenefitKind::Earned->value ? $benefit['offer_id'] : null,
                        'valid_weekdays' => $benefit['kind'] === PromotionBenefitKind::Direct->value
                            && isset($benefit['valid_weekdays']) ? json_encode($benefit['valid_weekdays'], JSON_THROW_ON_ERROR) : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                return $templateId;
            });
        } catch (UniqueConstraintViolationException) {
            throw new ConflictHttpException('This card design code is already in use.');
        }

        foreach ($this->templates($tenantId, $organizationUnitId) as $template) {
            if ((int) $template['id'] === $templateId) {
                return $template;
            }
        }

        throw new InvalidArgumentException('The new card design could not be loaded.');
    }

    public function cards(int $tenantId, ?int $organizationUnitId, ?int $customerId = null, ?int $vehicleId = null): array
    {
        return DB::table('promotion_cards as card')
            ->join('promotion_card_templates as template', 'template.id', '=', 'card.template_id')
            ->join('customers as customer', 'customer.id', '=', 'card.customer_id')
            ->join('vehicles as vehicle', 'vehicle.id', '=', 'card.vehicle_id')
            ->where('card.tenant_id', $tenantId)
            ->where(function ($query) use ($organizationUnitId): void {
                $query->whereNull('template.organization_unit_id')->orWhere('template.organization_unit_id', $organizationUnitId);
            })
            ->when($customerId !== null, static fn ($query) => $query->where('card.customer_id', $customerId))
            ->when($vehicleId !== null, static fn ($query) => $query->where('card.vehicle_id', $vehicleId))
            ->orderByDesc('card.id')
            ->limit(self::RECENT_CARD_LIMIT)
            ->get(['card.*', 'template.name as template_name', 'customer.name as customer_name', 'vehicle.registration_number as vehicle_registration_number'])
            ->map(static function ($card): array {
                $row = (array) $card;
                $row['template'] = ['id' => (int) $card->template_id, 'name' => $card->template_name];
                $row['customer'] = ['id' => (int) $card->customer_id, 'name' => $card->customer_name];
                $row['vehicle'] = ['id' => (int) $card->vehicle_id, 'name' => $card->vehicle_registration_number];

                return $row;
            })->all();
    }

    public function issue(int $tenantId, ?int $organizationUnitId, int $actorId, array $input): array
    {
        try {
            return DB::transaction(function () use ($tenantId, $organizationUnitId, $actorId, $input): array {
                $template = DB::table('promotion_card_templates')->where('tenant_id', $tenantId)
                    ->lockForUpdate()->find((int) $input['template_id']);
                if ($template === null || $template->status !== PromotionStatus::Active->value) {
                    throw new InvalidArgumentException('Select an active card template.');
                }
                $this->offers->assertBranch($template->organization_unit_id, $organizationUnitId);
                $today = now()->toDateString();
                if ($today < $template->starts_on || $today > $template->ends_on) {
                    throw new InvalidArgumentException('The card template is outside its valid period.');
                }
                $customerId = (int) $input['customer_id'];
                $vehicleId = (int) $input['vehicle_id'];
                $this->offers->customer($tenantId, $customerId);
                $this->offers->customerVehicle($tenantId, $organizationUnitId, $customerId, $vehicleId);
                $cardId = DB::table('promotion_cards')->insertGetId([
                    'tenant_id' => $tenantId,
                    'template_id' => $template->id,
                    'card_number' => trim((string) ($input['card_number'] ?? '')) === ''
                        ? self::GENERATED_CARD_PREFIX.Str::ulid()
                        : Str::upper(trim((string) $input['card_number'])),
                    'customer_id' => $customerId,
                    'issued_vehicle_id' => $vehicleId,
                    'vehicle_id' => $vehicleId,
                    'issued_on' => $today,
                    'expires_on' => $template->ends_on,
                    'status' => PromotionStatus::Active->value,
                    'row_version' => 1,
                    'issued_by' => $actorId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $countBenefits = DB::table('promotion_card_benefits')->where('tenant_id', $tenantId)
                    ->where('template_id', $template->id)->where('kind', PromotionBenefitKind::Earned->value)
                    ->orderBy('offer_id')->get();
                foreach ($countBenefits as $benefit) {
                    $offer = DB::table('promotion_offers')->where('tenant_id', $tenantId)
                        ->lockForUpdate()->find($benefit->offer_id);
                    if ($offer === null || $offer->status !== PromotionStatus::Active->value) {
                        throw new InvalidArgumentException('A count offer on this card is inactive.');
                    }
                    $startsOn = max($today, $template->starts_on, $offer->starts_on);
                    $endsOn = $offer->ends_on === null ? $template->ends_on : min($template->ends_on, $offer->ends_on);
                    if ($startsOn > $endsOn) {
                        throw new InvalidArgumentException('A count offer does not overlap the card period.');
                    }
                    $this->offers->assertNoOverlappingAssignment(
                        $tenantId, (int) $offer->id, $customerId, $vehicleId, $startsOn, $endsOn,
                    );
                    DB::table('promotion_offer_assignments')->insert([
                        'tenant_id' => $tenantId,
                        'offer_id' => $offer->id,
                        'customer_id' => $customerId,
                        'vehicle_id' => $vehicleId,
                        'card_id' => $cardId,
                        'starts_on' => $startsOn,
                        'ends_on' => $endsOn,
                        'status' => PromotionStatus::Active->value,
                        'row_version' => 1,
                        'assigned_by' => $actorId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                $this->lifecycle($tenantId, $cardId, PromotionCardAction::Issued->value,
                    null, $vehicleId, $actorId, null, PromotionStatus::Active->value);

                return (array) DB::table('promotion_cards')->where('tenant_id', $tenantId)->find($cardId);
            });
        } catch (UniqueConstraintViolationException) {
            throw new ConflictHttpException('The card number or a count offer assignment is already in use.');
        }
    }

    public function transfer(int $tenantId, ?int $organizationUnitId, int $actorId, int $cardId, int $newVehicleId, int $expectedVersion, string $reason): array
    {
        return DB::transaction(function () use ($tenantId, $organizationUnitId, $actorId, $cardId, $newVehicleId, $expectedVersion, $reason): array {
            $card = $this->lockedCard($tenantId, $cardId, $expectedVersion);
            $template = DB::table('promotion_card_templates')->where('tenant_id', $tenantId)->find($card->template_id);
            $this->offers->assertBranch($template->organization_unit_id, $organizationUnitId);
            if ($card->status !== PromotionStatus::Active->value || $card->expires_on < now()->toDateString()) {
                throw new ConflictHttpException('Only an active, unexpired card can be transferred.');
            }
            $this->offers->customerVehicle($tenantId, $organizationUnitId, (int) $card->customer_id, $newVehicleId);
            if ((int) $card->vehicle_id === $newVehicleId) {
                throw new InvalidArgumentException('Choose a different vehicle.');
            }
            DB::table('promotion_cards')->where('id', $cardId)->update([
                'vehicle_id' => $newVehicleId, 'row_version' => $expectedVersion + 1, 'updated_at' => now(),
            ]);
            $assignments = DB::table('promotion_offer_assignments')->where('tenant_id', $tenantId)
                ->where('card_id', $cardId)->where('status', PromotionStatus::Active->value)
                ->orderBy('offer_id')->get();
            foreach ($assignments as $assignment) {
                $offer = DB::table('promotion_offers')->where('tenant_id', $tenantId)
                    ->lockForUpdate()->find($assignment->offer_id);
                $this->offers->assertNoOverlappingAssignment(
                    $tenantId, (int) $offer->id, (int) $card->customer_id,
                    $newVehicleId, now()->toDateString(), $assignment->ends_on,
                );
                DB::table('promotion_offer_assignments')->where('id', $assignment->id)->update([
                    'status' => PromotionStatus::Transferred->value,
                    'row_version' => (int) $assignment->row_version + 1,
                    'updated_at' => now(),
                ]);
                DB::table('promotion_offer_assignments')->insert([
                    'tenant_id' => $tenantId,
                    'offer_id' => $assignment->offer_id,
                    'customer_id' => $card->customer_id,
                    'vehicle_id' => $newVehicleId,
                    'card_id' => $cardId,
                    'starts_on' => now()->toDateString(),
                    'ends_on' => $assignment->ends_on,
                    'status' => PromotionStatus::Active->value,
                    'row_version' => 1,
                    'assigned_by' => $actorId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $this->lifecycle($tenantId, $cardId, PromotionCardAction::Transferred->value,
                (int) $card->vehicle_id, $newVehicleId, $actorId,
                PromotionStatus::Active->value, PromotionStatus::Active->value, $reason);

            return (array) DB::table('promotion_cards')->where('tenant_id', $tenantId)->find($cardId);
        });
    }

    public function status(int $tenantId, ?int $organizationUnitId, int $actorId, int $cardId, string $status, int $expectedVersion, string $reason): array
    {
        return DB::transaction(function () use ($tenantId, $organizationUnitId, $actorId, $cardId, $status, $expectedVersion, $reason): array {
            $card = $this->lockedCard($tenantId, $cardId, $expectedVersion);
            $template = DB::table('promotion_card_templates')->where('tenant_id', $tenantId)->find($card->template_id);
            $this->offers->assertBranch($template->organization_unit_id, $organizationUnitId);
            if ($card->status === $status) {
                throw new ConflictHttpException('The card already has this status.');
            }
            if ($status === PromotionStatus::Active->value && $card->expires_on < now()->toDateString()) {
                throw new ConflictHttpException('An expired card cannot be reactivated.');
            }
            if ($status === PromotionStatus::Active->value) {
                $assignments = DB::table('promotion_offer_assignments')->where('tenant_id', $tenantId)
                    ->where('card_id', $cardId)->where('status', PromotionStatus::Paused->value)
                    ->orderBy('offer_id')->get();
                foreach ($assignments as $assignment) {
                    DB::table('promotion_offers')->where('tenant_id', $tenantId)
                        ->lockForUpdate()->find($assignment->offer_id);
                    $this->offers->assertNoOverlappingAssignment(
                        $tenantId,
                        (int) $assignment->offer_id,
                        (int) $assignment->customer_id,
                        $assignment->vehicle_id === null ? null : (int) $assignment->vehicle_id,
                        now()->toDateString(),
                        $assignment->ends_on,
                    );
                }
            }
            DB::table('promotion_cards')->where('id', $cardId)->update([
                'status' => $status, 'row_version' => $expectedVersion + 1, 'updated_at' => now(),
            ]);
            DB::table('promotion_offer_assignments')->where('tenant_id', $tenantId)->where('card_id', $cardId)
                ->whereIn('status', [PromotionStatus::Active->value, PromotionStatus::Paused->value])->update([
                    'status' => $status, 'row_version' => DB::raw('row_version + 1'), 'updated_at' => now(),
                ]);
            $this->lifecycle($tenantId, $cardId,
                $status === PromotionStatus::Active->value ? PromotionCardAction::Reactivated->value : PromotionCardAction::Paused->value,
                (int) $card->vehicle_id, (int) $card->vehicle_id, $actorId, $card->status, $status, $reason);

            return (array) DB::table('promotion_cards')->where('tenant_id', $tenantId)->find($cardId);
        });
    }

    private function lockedCard(int $tenantId, int $cardId, int $expectedVersion): object
    {
        $card = DB::table('promotion_cards')->where('tenant_id', $tenantId)->lockForUpdate()->find($cardId);
        if ($card === null) {
            abort(404);
        }
        if ((int) $card->row_version !== $expectedVersion) {
            throw new ConflictHttpException('The card changed. Refresh and try again.');
        }

        return $card;
    }

    private function lifecycle(
        int $tenantId,
        int $cardId,
        string $action,
        ?int $fromVehicleId,
        ?int $toVehicleId,
        int $actorId,
        ?string $oldStatus,
        string $newStatus,
        ?string $reason = null,
    ): void {
        $cardNumber = DB::table('promotion_cards')->where('tenant_id', $tenantId)->where('id', $cardId)->value('card_number');
        DB::table('promotion_card_lifecycle_events')->insert([
            'tenant_id' => $tenantId,
            'card_id' => $cardId,
            'action' => $action,
            'old_vehicle_id' => $fromVehicleId,
            'new_vehicle_id' => $toVehicleId,
            'card_number_snapshot' => $cardNumber,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'actor_id' => $actorId,
            'reason' => $reason,
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
