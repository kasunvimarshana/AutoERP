<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Payment\Enums\PaymentAllocationState;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\Payment;

final class PaymentCapabilityService
{
    public function __construct(private readonly DecimalMath $math) {}

    /**
     * @return array<string, bool>
     */
    public function capabilities(Payment $payment): array
    {
        $blockers = $this->blockers($payment);

        return array_map(static fn (array $reasons): bool => $reasons === [], $blockers);
    }

    /**
     * @return array<string, list<string>>
     */
    public function blockers(Payment $payment): array
    {
        $status = $this->status($payment);
        $postingStatus = $this->postingStatus($payment);
        $allocationStatus = $payment->allocation_status instanceof PaymentAllocationState
            ? $payment->allocation_status
            : PaymentAllocationState::from((string) $payment->allocation_status);

        $terminal = [PaymentStatus::Voided, PaymentStatus::Void, PaymentStatus::Cancelled, PaymentStatus::Reversed];
        $posted = $status === PaymentStatus::Posted && $postingStatus === PaymentPostingStatus::Posted;
        $hasJournal = $payment->finance_journal_entry_id !== null;
        $hasActiveAllocations = $payment->relationLoaded('allocations')
            ? $payment->allocations->contains(fn ($allocation): bool => (string) $allocation->status === 'active')
            : $payment->allocations()->where('status', 'active')->exists();

        $chequeLines = $payment->relationLoaded('lines')
            ? $payment->lines->filter(fn ($line): bool => $this->isChequeLine($line))
            : collect();
        $hasChequeLine = $payment->relationLoaded('lines')
            ? $chequeLines->isNotEmpty()
            : $payment->lines()->whereHas('paymentMethod', fn ($query) => $query->where('method_type', PaymentMethodType::Cheque->value))->exists();

        return [
            'can_edit' => $this->reasons($status !== PaymentStatus::Draft, 'Only draft payments can be edited.'),
            'can_submit' => $this->reasons($status !== PaymentStatus::Draft, 'Only draft payments can be submitted.'),
            'can_approve' => $this->reasons($status !== PaymentStatus::PendingApproval, 'Only pending approval payments can be approved.'),
            'can_post' => array_values(array_filter([
                $status !== PaymentStatus::Approved ? 'Only approved payments can be posted.' : null,
                $postingStatus === PaymentPostingStatus::Posted || $hasJournal ? 'Payment already has a posted Finance journal.' : null,
                $payment->lines()->count() < 1 ? 'Payment requires at least one line.' : null,
            ])),
            'can_allocate' => array_values(array_filter([
                ! $posted ? 'Only posted payments can be allocated to invoices.' : null,
                $this->math->compare((string) $payment->unapplied_amount, '0.000000') <= 0 ? 'Payment has no unapplied amount.' : null,
            ])),
            'can_refund' => array_values(array_filter([
                ! $posted ? 'Only posted payments can be refunded.' : null,
                $this->math->compare((string) $payment->unapplied_amount, '0.000000') <= 0 ? 'Payment has no refundable unapplied amount.' : null,
            ])),
            'can_void' => array_values(array_filter([
                ! in_array($status, [PaymentStatus::Draft, PaymentStatus::PendingApproval, PaymentStatus::Approved], true) ? 'Only unposted payments can be voided.' : null,
                $hasJournal || $postingStatus === PaymentPostingStatus::Posted ? 'Posted payments must be reversed, not voided.' : null,
                $hasActiveAllocations ? 'Allocated payments must be reversed before voiding.' : null,
            ])),
            'can_reverse' => array_values(array_filter([
                ! $posted ? 'Only posted payments can be reversed.' : null,
                in_array($status, $terminal, true) ? 'Terminal payments cannot be reversed.' : null,
                $payment->reversals()->exists() ? 'Payment already has a reversal.' : null,
            ])),
            'can_settle' => $this->reasons(in_array($status, $terminal, true), 'Terminal payments cannot be settled.'),
            'can_preview_cheque' => array_values(array_filter([
                ! $hasChequeLine ? 'Payment has no cheque-capable line.' : null,
                ! in_array($status, [PaymentStatus::Approved, PaymentStatus::Posted], true) ? 'Only approved or posted payments can be previewed for cheque printing.' : null,
            ])),
            'can_print_cheque' => array_values(array_filter([
                ! $hasChequeLine ? 'Payment has no cheque-capable line.' : null,
                ! in_array($status, [PaymentStatus::Approved, PaymentStatus::Posted], true) ? 'Only approved or posted payments can be printed.' : null,
            ])),
        ];
    }

    /** @return list<string> */
    private function reasons(bool $blocked, string $reason): array
    {
        return $blocked ? [$reason] : [];
    }

    private function status(Payment $payment): PaymentStatus
    {
        return $payment->status instanceof PaymentStatus
            ? $payment->status
            : PaymentStatus::from((string) $payment->status);
    }

    private function postingStatus(Payment $payment): PaymentPostingStatus
    {
        return $payment->posting_status instanceof PaymentPostingStatus
            ? $payment->posting_status
            : PaymentPostingStatus::from((string) $payment->posting_status);
    }

    private function isChequeLine(mixed $line): bool
    {
        $type = $line->paymentMethod?->method_type;
        $value = $type instanceof \BackedEnum ? $type->value : (string) $type;

        return $value === PaymentMethodType::Cheque->value;
    }
}
