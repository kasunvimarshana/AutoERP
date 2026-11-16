<?php

declare(strict_types=1);

namespace Modules\Promotion\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Services\DecimalMath;
use Modules\Promotion\Enums\PromotionStatus;

final class PromotionCardActivityService
{
    public function __construct(
        private readonly PromotionCardService $cards,
        private readonly PromotionRuntimeService $runtime,
        private readonly DecimalMath $math,
    ) {}

    public function list(int $tenantId, ?int $organizationUnitId, ?int $customerId): array
    {
        $today = now()->toDateString();

        return array_map(function (array $card) use ($tenantId, $today): array {
            $assignments = DB::table('promotion_offer_assignments as assignment')
                ->join('promotion_offers as offer', 'offer.id', '=', 'assignment.offer_id')
                ->join('items as qualifying', 'qualifying.id', '=', 'offer.qualifying_item_id')
                ->join('items as reward', 'reward.id', '=', 'offer.reward_item_id')
                ->where('assignment.tenant_id', $tenantId)->where('assignment.card_id', $card['id'])
                ->orderBy('assignment.id')
                ->get(['assignment.*', 'offer.name as offer_name', 'offer.paid_quantity',
                    'offer.reward_quantity', 'qualifying.name as qualifying_item_name',
                    'reward.name as reward_item_name'])
                ->map(function ($assignment) use ($tenantId, $today): array {
                    $progress = $this->runtime->progress($tenantId, $assignment);
                    $status = $assignment->status === PromotionStatus::Exhausted->value
                        ? 'completed' : $assignment->status;
                    if ($status === 'completed' && ($this->math->compare($progress['available_rewards'], '0') > 0
                        || $this->math->compare($progress['qualifying_quantity'], (string) $assignment->paid_quantity) < 0)) {
                        $status = 'review_required';
                    }
                    if ($status === PromotionStatus::Active->value && $assignment->ends_on !== null
                        && $assignment->ends_on < $today) {
                        $status = PromotionStatus::Expired->value;
                    } elseif ($status === PromotionStatus::Active->value
                        && $this->math->compare($progress['available_rewards'], '0') > 0) {
                        $status = 'free_ready';
                    } elseif ($status === PromotionStatus::Active->value
                        && $this->math->compare($progress['redeemed_rewards'], '0') > 0) {
                        $status = 'free_redeemed';
                    } elseif ($status === PromotionStatus::Active->value
                        && $this->math->compare($progress['qualifying_quantity'], '0') > 0) {
                        $status = 'count_progress';
                    } elseif ($status === PromotionStatus::Active->value) {
                        $status = 'assigned';
                    }

                    return array_merge((array) $assignment, $progress, ['status_label' => $status]);
                })->all();
            $card['status_label'] = $card['expires_on'] < $today
                ? PromotionStatus::Expired->value
                : ($card['status'] === PromotionStatus::Exhausted->value ? 'completed' : $card['status']);
            if (collect($assignments)->contains(static fn (array $assignment): bool => $assignment['status_label'] === 'review_required')) {
                $card['status_label'] = 'review_required';
            }
            $card['assignments'] = $assignments;

            return $card;
        }, $this->cards->cards($tenantId, $organizationUnitId, $customerId));
    }

    public function invoices(int $tenantId, ?int $organizationUnitId, int $cardId): array
    {
        $card = DB::table('promotion_cards as card')
            ->join('promotion_card_templates as template', 'template.id', '=', 'card.template_id')
            ->where('card.tenant_id', $tenantId)
            ->where(fn ($query) => $query->whereNull('template.organization_unit_id')
                ->orWhere('template.organization_unit_id', $organizationUnitId))
            ->where('card.id', $cardId)->first(['card.id']);
        if ($card === null) {
            abort(404);
        }
        $assignmentIds = DB::table('promotion_offer_assignments')->where('tenant_id', $tenantId)
            ->where('card_id', $card->id)->pluck('id');
        $qualifications = DB::table('promotion_qualification_events as event')
            ->join('invoice_lines as line', 'line.id', '=', 'event.invoice_line_id')
            ->join('invoices as invoice', 'invoice.id', '=', 'line.invoice_id')
            ->where('event.tenant_id', $tenantId)->whereIn('event.offer_assignment_id', $assignmentIds)
            ->get(['event.event_type', 'event.quantity', 'event.charged_amount_snapshot as amount',
                'invoice.id as invoice_id', 'invoice.invoice_number', 'invoice.invoice_date']);
        $redemptions = DB::table('promotion_redemption_events as event')
            ->join('invoice_lines as line', 'line.id', '=', 'event.invoice_line_id')
            ->join('invoices as invoice', 'invoice.id', '=', 'line.invoice_id')
            ->where('event.tenant_id', $tenantId)->where('event.card_id', $cardId)
            ->get(['event.event_type', 'event.quantity', 'event.discount_amount_snapshot as amount',
                'invoice.id as invoice_id', 'invoice.invoice_number', 'invoice.invoice_date']);

        return ['qualifications' => $qualifications->all(), 'redemptions' => $redemptions->all()];
    }
}
