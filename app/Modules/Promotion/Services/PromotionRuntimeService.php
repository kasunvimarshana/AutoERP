<?php

declare(strict_types=1);

namespace Modules\Promotion\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Promotion\Enums\PromotionBenefitKind;
use Modules\Promotion\Enums\PromotionDiscountType;
use Modules\Promotion\Enums\PromotionEventType;
use Modules\Promotion\Enums\PromotionStatus;
use Modules\VehicleService\Models\VehicleServiceJob;
use Modules\VehicleService\Models\VehicleServiceJobLine;
use Modules\VehicleService\Services\VehicleServiceLineCalculationService;
use Modules\VehicleService\Services\VehicleServiceValidationService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class PromotionRuntimeService
{
    private const ZERO = '0.000000';

    private const SOURCE_LINE_TYPE = 'vehicle_service_job_line';

    private const ONE_USE = '1.000000';

    private const PERCENT_BASE = '100';

    public function __construct(
        private readonly DecimalMath $math,
        private readonly PromotionOfferService $offers,
        private readonly VehicleServiceValidationService $jobs,
        private readonly VehicleServiceLineCalculationService $calculations,
    ) {}

    public function available(int $tenantId, ?int $organizationUnitId, int $customerId, int $vehicleId): array
    {
        $this->offers->customerVehicle($tenantId, $organizationUnitId, $customerId, $vehicleId);
        $today = now()->toDateString();
        $cards = DB::table('promotion_cards as card')
            ->join('promotion_card_templates as template', 'template.id', '=', 'card.template_id')
            ->where('card.tenant_id', $tenantId)->where('card.customer_id', $customerId)
            ->where('card.vehicle_id', $vehicleId)->where('card.status', PromotionStatus::Active->value)
            ->where('template.status', PromotionStatus::Active->value)
            ->where('card.expires_on', '>=', $today)
            ->where('template.starts_on', '<=', $today)->where('template.ends_on', '>=', $today)
            ->where(function ($query) use ($organizationUnitId): void {
                $query->whereNull('template.organization_unit_id')->orWhere('template.organization_unit_id', $organizationUnitId);
            })
            ->get(['card.*', 'template.name as template_name']);
        $cardRows = $cards->map(function ($card) use ($tenantId, $today): array {
            $benefits = DB::table('promotion_card_benefits as benefit')
                ->leftJoin('items as item', 'item.id', '=', 'benefit.item_id')
                ->where('benefit.tenant_id', $tenantId)->where('benefit.template_id', $card->template_id)
                ->orderBy('benefit.position')->get(['benefit.*', 'item.code as item_code', 'item.name as item_name']);
            $row = (array) $card;
            $row['template'] = ['id' => (int) $card->template_id, 'name' => $card->template_name];
            $row['benefits'] = $benefits->map(function ($benefit) use ($tenantId, $card, $today): array {
                $data = (array) $benefit;
                $data['item'] = $benefit->item_id === null ? null : ['id' => (int) $benefit->item_id,
                    'code' => $benefit->item_code, 'name' => $benefit->item_name];
                $data['available_today'] = $this->validWeekday($benefit->valid_weekdays, $today);
                if ($benefit->kind === PromotionBenefitKind::Direct->value) {
                    $used = $this->redemptionQuantity($tenantId, (int) $card->id, (int) $benefit->id, null);
                    $data['remaining_uses'] = max(0, (int) $benefit->use_limit - (int) $used);
                }

                return $data;
            })->all();

            return $row;
        })->all();
        $assignments = DB::table('promotion_offer_assignments as assignment')
            ->join('promotion_offers as offer', 'offer.id', '=', 'assignment.offer_id')
            ->join('items as qualifying', 'qualifying.id', '=', 'offer.qualifying_item_id')
            ->join('items as reward', 'reward.id', '=', 'offer.reward_item_id')
            ->where('assignment.tenant_id', $tenantId)->where('assignment.customer_id', $customerId)
            ->where(function ($query) use ($vehicleId): void {
                $query->whereNull('assignment.vehicle_id')->orWhere('assignment.vehicle_id', $vehicleId);
            })
            ->where('assignment.status', PromotionStatus::Active->value)
            ->where('offer.status', PromotionStatus::Active->value)
            ->where('assignment.starts_on', '<=', $today)
            ->where(fn ($query) => $query->whereNull('assignment.ends_on')->orWhere('assignment.ends_on', '>=', $today))
            ->where(function ($query) use ($organizationUnitId): void {
                $query->whereNull('offer.organization_unit_id')->orWhere('offer.organization_unit_id', $organizationUnitId);
            })
            ->get(['assignment.*', 'offer.name as offer_name', 'offer.qualifying_item_id', 'offer.reward_item_id',
                'offer.paid_quantity', 'offer.reward_quantity', 'qualifying.code as qualifying_item_code',
                'qualifying.name as qualifying_item_name', 'reward.code as reward_item_code',
                'reward.name as reward_item_name']);
        $offerRows = $assignments->map(function ($assignment) use ($tenantId): array {
            $progress = $this->progress($tenantId, $assignment);

            return array_merge((array) $assignment, $progress, [
                'qualifying_item' => ['id' => (int) $assignment->qualifying_item_id,
                    'code' => $assignment->qualifying_item_code, 'name' => $assignment->qualifying_item_name],
                'reward_item' => ['id' => (int) $assignment->reward_item_id,
                    'code' => $assignment->reward_item_code, 'name' => $assignment->reward_item_name],
            ]);
        })->all();

        $assignedOfferIds = DB::table('promotion_offer_assignments')
            ->where('tenant_id', $tenantId)
            ->where(function ($query) use ($customerId, $vehicleId) {
                $query->where('vehicle_id', $vehicleId)
                    ->orWhere(function ($customerQuery) use ($customerId) {
                        $customerQuery->where('customer_id', $customerId)
                            ->whereNull('vehicle_id');
                    });
            })
            ->where('status', PromotionStatus::Active->value)
            ->where(fn ($query) => $query->whereNull('ends_on')->orWhere('ends_on', '>=', $today))
            ->pluck('offer_id');
        $unassignedOffers = DB::table('promotion_offers as offer')
            ->join('items as qualifying', 'qualifying.id', '=', 'offer.qualifying_item_id')
            ->where('offer.tenant_id', $tenantId)
            ->where('offer.status', PromotionStatus::Active->value)
            ->where('offer.starts_on', '<=', $today)
            ->where(fn ($query) => $query->whereNull('offer.ends_on')->orWhere('offer.ends_on', '>=', $today))
            ->where(fn ($query) => $query->whereNull('offer.organization_unit_id')->orWhere('offer.organization_unit_id', $organizationUnitId))
            ->whereNotIn('offer.id', $assignedOfferIds)
            ->orderBy('offer.name')->get(['offer.id', 'offer.name', 'offer.count_scope',
                'qualifying.code as paid_item_code', 'qualifying.name as paid_item_name']);

        return ['cards' => $cardRows, 'offers' => $offerRows, 'unassigned_offers' => $unassignedOffers];
    }

    public function choose(int $tenantId, ?int $organizationUnitId, int $actorId, int $jobId, int $lineId, int $expectedVersion, array $input): array
    {
        $hasCardBenefit = ! empty($input['card_benefit_id']);
        $hasOfferReward = ! empty($input['offer_assignment_id']);
        if ($hasCardBenefit === $hasOfferReward
            || ($hasCardBenefit && empty($input['card_id']))
            || ($hasOfferReward && ! empty($input['card_id']))) {
            throw new InvalidArgumentException('Choose one card benefit or one earned offer reward.');
        }

        return DB::transaction(function () use ($tenantId, $organizationUnitId, $actorId, $jobId, $lineId, $expectedVersion, $input): array {
            $job = VehicleServiceJob::query()->forContext($tenantId, $organizationUnitId)->lockForUpdate()->findOrFail($jobId);
            $this->assertVersion($job, $expectedVersion);
            $this->jobs->assertMutable($job);
            $line = $job->lines()->lockForUpdate()->findOrFail($lineId);
            if (! $line->is_billable || $line->item_id === null) {
                throw new InvalidArgumentException('Choose a billable service item line.');
            }
            if ($this->hasActiveInvoice($tenantId, $lineId)) {
                throw new ConflictHttpException('A promotion cannot be changed after this line has been invoiced.');
            }
            $selection = $this->resolveChoice($tenantId, $job, $line, $input);
            $available = $this->available($tenantId, $organizationUnitId, (int) $job->customer_id, (int) $job->vehicle_id);
            foreach ($available['cards'] as $card) {
                foreach ($card['benefits'] as $benefit) {
                    if ($benefit['kind'] !== PromotionBenefitKind::Direct->value
                        || (int) $benefit['item_id'] !== (int) $line->item_id
                        || ! $benefit['available_today'] || (int) $benefit['remaining_uses'] < 1) {
                        continue;
                    }
                    try {
                        $candidate = $this->resolveChoice($tenantId, $job, $line, [
                            'card_id' => $card['id'], 'card_benefit_id' => $benefit['id'],
                        ]);
                    } catch (ConflictHttpException) {
                        continue;
                    }
                    if ($this->math->compare($candidate['discount'], $selection['discount']) > 0) {
                        $selection = $candidate;
                    }
                }
            }
            foreach ($available['offers'] as $offer) {
                if ((int) $offer['reward_item_id'] !== (int) $line->item_id
                    || $this->math->compare($offer['available_rewards'], (string) $line->quantity) < 0) {
                    continue;
                }
                try {
                    $candidate = $this->resolveChoice($tenantId, $job, $line, ['offer_assignment_id' => $offer['id']]);
                } catch (ConflictHttpException) {
                    continue;
                }
                if ($this->math->compare($candidate['discount'], $selection['discount']) > 0) {
                    $selection = $candidate;
                }
            }
            $discount = $selection['discount'];
            $current = DB::table('promotion_job_line_choices')->where('tenant_id', $tenantId)->where('job_line_id', $lineId)->first();
            if ($current === null) {
                DB::table('promotion_job_line_choices')->insert([
                    'tenant_id' => $tenantId, 'job_line_id' => $lineId,
                    'card_id' => $selection['card_id'], 'card_benefit_id' => $selection['card_benefit_id'],
                    'offer_assignment_id' => $selection['offer_assignment_id'], 'row_version' => 1,
                    'selected_by' => $actorId, 'created_at' => now(), 'updated_at' => now(),
                ]);
            } else {
                DB::table('promotion_job_line_choices')->where('id', $current->id)->update([
                    'card_id' => $selection['card_id'], 'card_benefit_id' => $selection['card_benefit_id'],
                    'offer_assignment_id' => $selection['offer_assignment_id'],
                    'row_version' => (int) $current->row_version + 1, 'selected_by' => $actorId, 'updated_at' => now(),
                ]);
            }
            $this->applyLineDiscount($job, $line, $discount);

            return ['discount_amount' => $discount, 'job_row_version' => (int) $job->refresh()->row_version,
                'card_id' => $selection['card_id'], 'card_benefit_id' => $selection['card_benefit_id'],
                'offer_assignment_id' => $selection['offer_assignment_id']];
        });
    }

    public function removeChoice(int $tenantId, ?int $organizationUnitId, int $jobId, int $lineId, int $expectedVersion): array
    {
        return DB::transaction(function () use ($tenantId, $organizationUnitId, $jobId, $lineId, $expectedVersion): array {
            $job = VehicleServiceJob::query()->forContext($tenantId, $organizationUnitId)->lockForUpdate()->findOrFail($jobId);
            $this->assertVersion($job, $expectedVersion);
            $this->jobs->assertMutable($job);
            $line = $job->lines()->lockForUpdate()->findOrFail($lineId);
            if ($this->hasActiveInvoice($tenantId, $lineId)) {
                throw new ConflictHttpException('A promotion cannot be removed after this line has been invoiced.');
            }
            DB::table('promotion_job_line_choices')->where('tenant_id', $tenantId)->where('job_line_id', $lineId)->delete();
            $this->applyLineDiscount($job, $line, self::ZERO);

            return ['job_row_version' => (int) $job->refresh()->row_version];
        });
    }

    public function clearLineChoice(int $tenantId, int $lineId): void
    {
        DB::table('promotion_job_line_choices')->where('tenant_id', $tenantId)
            ->where('job_line_id', $lineId)->delete();
    }

    public function hasJobChoices(VehicleServiceJob $job): bool
    {
        return DB::table('promotion_job_line_choices as choice')
            ->join('vehicle_service_job_lines as line', 'line.id', '=', 'choice.job_line_id')
            ->where('choice.tenant_id', $job->tenant_id)
            ->where('line.vehicle_service_job_id', $job->getKey())->exists();
    }

    public function assertInvoiceChoices(VehicleServiceJob $job, array $lineQuantities): void
    {
        $choices = DB::table('promotion_job_line_choices as choice')
            ->join('vehicle_service_job_lines as line', 'line.id', '=', 'choice.job_line_id')
            ->where('choice.tenant_id', $job->tenant_id)->where('line.vehicle_service_job_id', $job->getKey())->get();
        $selectedByBenefit = [];
        $selectedByAssignment = [];
        foreach ($choices as $choice) {
            if ($lineQuantities !== [] && ! array_key_exists((int) $choice->job_line_id, $lineQuantities)) {
                continue;
            }
            $line = $job->lines()->findOrFail($choice->job_line_id);
            if ($lineQuantities !== [] && $this->math->isZero($lineQuantities[(int) $choice->job_line_id])) {
                continue;
            }
            if ($lineQuantities !== [] && $this->math->compare(
                $lineQuantities[(int) $choice->job_line_id],
                (string) $line->quantity,
            ) !== 0) {
                throw new ConflictHttpException('Invoice the full promotion service line as one use.');
            }
            if ($this->hasActiveInvoice((int) $job->tenant_id, (int) $line->id)) {
                continue;
            }
            $selection = $this->resolveChoice((int) $job->tenant_id, $job, $line, (array) $choice);
            if ($this->math->compare($selection['discount'], (string) $line->promotion_discount_amount) !== 0) {
                throw new ConflictHttpException('Promotion value changed. Refresh the service job before invoicing.');
            }
            if ($choice->card_benefit_id !== null) {
                $key = (int) $choice->card_benefit_id;
                $selectedByBenefit[$key] = $this->math->add($selectedByBenefit[$key] ?? self::ZERO, (string) $line->quantity);
            }
            if ($choice->offer_assignment_id !== null) {
                $key = (int) $choice->offer_assignment_id;
                $selectedByAssignment[$key] = $this->math->add($selectedByAssignment[$key] ?? self::ZERO, (string) $line->quantity);
            }
        }
        foreach ($selectedByBenefit as $benefitId => $quantity) {
            $benefit = DB::table('promotion_card_benefits')->where('tenant_id', $job->tenant_id)->find($benefitId);
            $cardId = $choices->firstWhere('card_benefit_id', $benefitId)->card_id;
            $used = $this->redemptionQuantity((int) $job->tenant_id, (int) $cardId, $benefitId, null);
            if ($this->math->compare($this->math->add($used, $quantity), (string) $benefit->use_limit) > 0) {
                throw new ConflictHttpException('The card benefit has already been used on another service job.');
            }
        }
        foreach ($selectedByAssignment as $assignmentId => $quantity) {
            $assignment = DB::table('promotion_offer_assignments as assignment')
                ->join('promotion_offers as offer', 'offer.id', '=', 'assignment.offer_id')
                ->where('assignment.tenant_id', $job->tenant_id)->where('assignment.id', $assignmentId)
                ->first(['assignment.*', 'offer.paid_quantity', 'offer.reward_quantity']);
            if ($assignment === null || $this->math->compare($this->progress((int) $job->tenant_id, $assignment)['available_rewards'], $quantity) < 0) {
                throw new ConflictHttpException('There are not enough earned free uses for these lines.');
            }
        }
    }

    public function recordPostedInvoice(VehicleServiceJob $job, Invoice $invoice, ?int $actorId): void
    {
        $tenantId = (int) $job->tenant_id;
        $invoiceLines = $invoice->lines()->where('source_line_type', self::SOURCE_LINE_TYPE)->get();
        $serviceBase = $this->math->sum($invoiceLines->map(fn ($line): string => $this->math->sub(
            $this->math->mul((string) $line->quantity, (string) $line->unit_price),
            (string) $line->discount_amount,
        )));
        $wholeJobDiscount = $this->math->normalize((string) $invoice->adjustments()
            ->where('source_adjustment_type', 'vehicle_service_job_discount')->sum('amount'));
        foreach ($invoiceLines as $invoiceLine) {
            $line = $job->lines()->findOrFail($invoiceLine->source_line_id);
            $choice = DB::table('promotion_job_line_choices')->where('tenant_id', $tenantId)->where('job_line_id', $line->id)->first();
            if ($choice !== null && ! DB::table('promotion_redemption_events')->where('tenant_id', $tenantId)
                ->where('invoice_line_id', $invoiceLine->id)
                ->where('event_type', PromotionEventType::Redemption->value)->exists()) {
                $ratio = $this->math->div((string) $invoiceLine->quantity, (string) $line->quantity, 12);
                $discount = $this->math->mul((string) $line->promotion_discount_amount, $ratio);
                DB::table('promotion_redemption_events')->insert([
                    'tenant_id' => $tenantId, 'card_id' => $choice->card_id,
                    'card_benefit_id' => $choice->card_benefit_id, 'offer_assignment_id' => $choice->offer_assignment_id,
                    'invoice_line_id' => $invoiceLine->id, 'event_type' => PromotionEventType::Redemption->value,
                    'quantity' => $invoiceLine->quantity, 'unit_price_snapshot' => $line->unit_price,
                    'discount_amount_snapshot' => $discount, 'reverses_event_id' => null,
                    'actor_id' => $actorId, 'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            $chargedServiceAmount = $this->math->sub(
                $this->math->mul((string) $invoiceLine->quantity, (string) $invoiceLine->unit_price),
                (string) $invoiceLine->discount_amount,
            );
            if ($this->math->compare($chargedServiceAmount, self::ZERO) <= 0
                || $this->math->compare($serviceBase, $wholeJobDiscount) <= 0) {
                continue;
            }
            $assignments = DB::table('promotion_offer_assignments as assignment')
                ->join('promotion_offers as offer', 'offer.id', '=', 'assignment.offer_id')
                ->where('assignment.tenant_id', $tenantId)->where('assignment.customer_id', $job->customer_id)
                ->where('offer.qualifying_item_id', $line->item_id)
                ->where('assignment.status', PromotionStatus::Active->value)
                ->where('offer.status', PromotionStatus::Active->value)
                ->where('assignment.starts_on', '<=', now()->toDateString())
                ->where(fn ($query) => $query->whereNull('assignment.ends_on')->orWhere('assignment.ends_on', '>=', now()->toDateString()))
                ->where(function ($query) use ($job): void {
                    $query->whereNull('assignment.vehicle_id')->orWhere('assignment.vehicle_id', $job->vehicle_id);
                })->get(['assignment.*']);
            foreach ($assignments as $assignment) {
                if (DB::table('promotion_qualification_events')->where('tenant_id', $tenantId)
                    ->where('offer_assignment_id', $assignment->id)
                    ->where('invoice_line_id', $invoiceLine->id)
                    ->where('event_type', PromotionEventType::Qualification->value)->exists()) {
                    continue;
                }
                DB::table('promotion_qualification_events')->insert([
                    'tenant_id' => $tenantId, 'offer_assignment_id' => $assignment->id,
                    'invoice_line_id' => $invoiceLine->id, 'event_type' => PromotionEventType::Qualification->value,
                    'quantity' => $invoiceLine->quantity, 'charged_amount_snapshot' => $chargedServiceAmount,
                    'reverses_event_id' => null, 'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function reverseInvoice(int $tenantId, int $invoiceId, ?int $actorId): void
    {
        $qualifications = DB::table('promotion_qualification_events as event')
            ->join('invoice_lines as line', 'line.id', '=', 'event.invoice_line_id')
            ->where('event.tenant_id', $tenantId)->where('line.invoice_id', $invoiceId)
            ->where('event.event_type', PromotionEventType::Qualification->value)->get(['event.*']);
        foreach ($qualifications as $event) {
            if (DB::table('promotion_qualification_events')->where('reverses_event_id', $event->id)->exists()) {
                continue;
            }
            DB::table('promotion_qualification_events')->insert([
                'tenant_id' => $tenantId, 'offer_assignment_id' => $event->offer_assignment_id,
                'invoice_line_id' => $event->invoice_line_id, 'event_type' => PromotionEventType::Correction->value,
                'quantity' => $this->math->sub(self::ZERO, (string) $event->quantity),
                'charged_amount_snapshot' => $this->math->sub(self::ZERO, (string) $event->charged_amount_snapshot),
                'reverses_event_id' => $event->id, 'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $redemptions = DB::table('promotion_redemption_events as event')
            ->join('invoice_lines as line', 'line.id', '=', 'event.invoice_line_id')
            ->where('event.tenant_id', $tenantId)->where('line.invoice_id', $invoiceId)
            ->where('event.event_type', PromotionEventType::Redemption->value)->get(['event.*']);
        foreach ($redemptions as $event) {
            if (DB::table('promotion_redemption_events')->where('reverses_event_id', $event->id)->exists()) {
                continue;
            }
            DB::table('promotion_redemption_events')->insert([
                'tenant_id' => $tenantId, 'card_id' => $event->card_id,
                'card_benefit_id' => $event->card_benefit_id, 'offer_assignment_id' => $event->offer_assignment_id,
                'invoice_line_id' => $event->invoice_line_id, 'event_type' => PromotionEventType::Correction->value,
                'quantity' => $this->math->sub(self::ZERO, (string) $event->quantity),
                'unit_price_snapshot' => $event->unit_price_snapshot,
                'discount_amount_snapshot' => $this->math->sub(self::ZERO, (string) $event->discount_amount_snapshot),
                'reverses_event_id' => $event->id, 'actor_id' => $actorId,
                'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function resolveChoice(int $tenantId, VehicleServiceJob $job, VehicleServiceJobLine $line, array $input): array
    {
        $today = now()->toDateString();
        if ($this->math->compare((string) $line->quantity, self::ONE_USE) !== 0) {
            throw new InvalidArgumentException('Add one promotion service per line.');
        }
        $base = $this->math->sub($this->math->mul((string) $line->quantity, (string) $line->unit_price), (string) $line->discount_amount);
        if ($this->math->compare($base, self::ZERO) <= 0) {
            throw new InvalidArgumentException('This line has no remaining amount for an automatic offer.');
        }
        if (! empty($input['card_benefit_id'])) {
            $benefit = DB::table('promotion_card_benefits')->where('tenant_id', $tenantId)->find((int) $input['card_benefit_id']);
            $card = DB::table('promotion_cards as card')
                ->join('promotion_card_templates as template', 'template.id', '=', 'card.template_id')
                ->where('card.tenant_id', $tenantId)->where('card.id', (int) $input['card_id'])
                ->lockForUpdate()->first(['card.*', 'template.status as template_status',
                    'template.starts_on as template_starts_on', 'template.ends_on as template_ends_on',
                    'template.organization_unit_id as template_organization_unit_id']);
            if ($benefit === null || $card === null || (int) $benefit->template_id !== (int) $card->template_id
                || $benefit->kind !== PromotionBenefitKind::Direct->value || (int) $benefit->item_id !== (int) $line->item_id
                || (int) $card->customer_id !== (int) $job->customer_id || (int) $card->vehicle_id !== (int) $job->vehicle_id
                || $card->status !== PromotionStatus::Active->value || $card->expires_on < $today
                || $card->template_status !== PromotionStatus::Active->value
                || ($card->template_organization_unit_id !== null
                    && (int) $card->template_organization_unit_id !== (int) $job->organization_unit_id)
                || $card->template_starts_on > $today || $card->template_ends_on < $today
                || ! $this->validWeekday($benefit->valid_weekdays, $today)) {
                throw new ConflictHttpException('This card benefit is unavailable for the selected service.');
            }
            $used = $this->redemptionQuantity($tenantId, (int) $card->id, (int) $benefit->id, null);
            $pendingQuantity = $this->pendingQuantity($tenantId, $job, $line, 'card_benefit_id', (int) $benefit->id);
            if ($this->math->compare($this->math->add($this->math->add($used, $pendingQuantity), (string) $line->quantity), (string) $benefit->use_limit) > 0) {
                throw new ConflictHttpException('No card uses remain for this service quantity.');
            }
            $discount = $benefit->discount_type === PromotionDiscountType::Free->value
                ? $base
                : $this->math->div($this->math->mul($base, (string) $benefit->discount_rate), self::PERCENT_BASE);

            return ['discount' => $discount, 'card_id' => $card->id,
                'card_benefit_id' => $benefit->id, 'offer_assignment_id' => null];
        }
        $assignment = DB::table('promotion_offer_assignments')->where('tenant_id', $tenantId)
            ->lockForUpdate()->find((int) $input['offer_assignment_id']);
        $offer = $assignment === null ? null : DB::table('promotion_offers')->where('tenant_id', $tenantId)->find($assignment->offer_id);
        if ($assignment === null || $offer === null || $assignment->status !== PromotionStatus::Active->value
            || $offer->status !== PromotionStatus::Active->value || $assignment->starts_on > $today
            || ($assignment->ends_on !== null && $assignment->ends_on < $today) || (int) $assignment->customer_id !== (int) $job->customer_id
            || ($offer->organization_unit_id !== null && (int) $offer->organization_unit_id !== (int) $job->organization_unit_id)
            || ($assignment->vehicle_id !== null && (int) $assignment->vehicle_id !== (int) $job->vehicle_id)
            || (int) $offer->reward_item_id !== (int) $line->item_id) {
            throw new ConflictHttpException('This earned offer is unavailable for the selected service.');
        }
        if ($assignment->card_id !== null) {
            $card = DB::table('promotion_cards')->where('tenant_id', $tenantId)->lockForUpdate()->find($assignment->card_id);
            if ($card === null || $card->status !== PromotionStatus::Active->value || $card->expires_on < $today
                || (int) $card->vehicle_id !== (int) $job->vehicle_id) {
                throw new ConflictHttpException('The linked card is unavailable.');
            }
        }
        $progress = $this->progress($tenantId, (object) array_merge((array) $assignment,
            ['paid_quantity' => $offer->paid_quantity, 'reward_quantity' => $offer->reward_quantity]));
        $pendingQuantity = $this->pendingQuantity($tenantId, $job, $line, 'offer_assignment_id', (int) $assignment->id);
        if ($this->math->compare($progress['available_rewards'], $this->math->add($pendingQuantity, (string) $line->quantity)) < 0) {
            throw new ConflictHttpException('The earned free service is not yet available.');
        }

        return ['discount' => $base, 'card_id' => $assignment->card_id,
            'card_benefit_id' => null, 'offer_assignment_id' => $assignment->id];
    }

    private function progress(int $tenantId, object $assignment): array
    {
        $qualifying = $this->math->sum(DB::table('promotion_qualification_events')
            ->where('tenant_id', $tenantId)->where('offer_assignment_id', $assignment->id)->pluck('quantity'));
        $redeemed = $this->redemptionQuantity($tenantId, null, null, (int) $assignment->id);
        $earnedCycles = bcdiv($qualifying, (string) $assignment->paid_quantity, 0);
        $earned = $this->math->mul($earnedCycles, (string) $assignment->reward_quantity);
        $progress = $this->math->sub($qualifying, $this->math->mul($earnedCycles, (string) $assignment->paid_quantity));

        return ['qualifying_quantity' => $qualifying, 'progress_quantity' => $progress,
            'remaining_to_next' => $this->math->sub((string) $assignment->paid_quantity, $progress),
            'available_rewards' => $this->math->sub($earned, $redeemed)];
    }

    private function redemptionQuantity(int $tenantId, ?int $cardId, ?int $benefitId, ?int $assignmentId): string
    {
        $query = DB::table('promotion_redemption_events')->where('tenant_id', $tenantId);
        if ($assignmentId !== null) {
            $query->where('offer_assignment_id', $assignmentId);
        } else {
            $query->where('card_id', $cardId)->where('card_benefit_id', $benefitId);
        }

        return $this->math->sum($query->pluck('quantity'));
    }

    private function validWeekday(?string $json, string $date): bool
    {
        if ($json === null) {
            return true;
        }
        $days = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        return in_array((int) date('N', strtotime($date)), $days, true);
    }

    private function pendingQuantity(int $tenantId, VehicleServiceJob $job, VehicleServiceJobLine $line, string $column, int $value): string
    {
        return $this->math->sum(DB::table('promotion_job_line_choices as choice')
            ->join('vehicle_service_job_lines as job_line', 'job_line.id', '=', 'choice.job_line_id')
            ->where('choice.tenant_id', $tenantId)->where('job_line.vehicle_service_job_id', $job->getKey())
            ->where('choice.'.$column, $value)->where('job_line.id', '!=', $line->getKey())
            ->pluck('job_line.quantity'));
    }

    private function applyLineDiscount(VehicleServiceJob $job, VehicleServiceJobLine $line, string $discount): void
    {
        $previous = (string) $line->promotion_discount_amount;
        $line->promotion_discount_amount = $discount;
        $line->line_total = $this->math->sub((string) $line->line_total, $this->math->sub($discount, $previous));
        $line->save();
        $this->calculations->recalculateAssignments($line);
        $this->calculations->recalculateJob($job);
    }

    private function assertVersion(VehicleServiceJob $job, int $expectedVersion): void
    {
        if ((int) $job->row_version !== $expectedVersion) {
            throw new ConflictHttpException('The service job changed. Refresh and try again.');
        }
    }

    private function hasActiveInvoice(int $tenantId, int $lineId): bool
    {
        return DB::table('invoice_source_lines as source')
            ->join('invoices as invoice', 'invoice.id', '=', 'source.invoice_id')
            ->where('source.tenant_id', $tenantId)
            ->where('source.source_line_type', self::SOURCE_LINE_TYPE)
            ->where('source.source_line_id', $lineId)
            ->whereNotIn('invoice.status', [
                InvoiceStatus::Cancelled->value,
                InvoiceStatus::Void->value,
                InvoiceStatus::Reversed->value,
            ])
            ->exists();
    }
}
