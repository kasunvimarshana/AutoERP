<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Modules\Core\Services\DecimalMath;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Enums\PaymentAllocationState;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentMethodType;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Models\Payment;

final class PaymentCapabilityService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function capabilities(Payment $payment): array
    {
        return array_map(static fn (array $reasons): bool => $reasons === [], $this->blockers($payment));
    }

    public function blockers(Payment $payment): array
    {
        $document = $this->documentStatus($payment);
        $posting = $this->postingStatus($payment);
        $allocation = $payment->allocation_status instanceof PaymentAllocationState
            ? $payment->allocation_status
            : PaymentAllocationState::from((string) $payment->allocation_status);
        $posted = $document === PaymentDocumentStatus::Approved && $posting === PaymentPostingStatus::Posted;
        $hasPostingReference = trim((string) $payment->finance_posting_reference) !== '';
        $terminal = in_array($document, [PaymentDocumentStatus::Voided, PaymentDocumentStatus::Reversed], true);
        $hasActiveAllocations = $payment->relationLoaded('allocations')
            ? $payment->allocations->contains(fn ($row): bool => $this->stateValue($row->status) === AllocationStatus::Active->value)
            : $payment->allocations()->where('status', AllocationStatus::Active->value)->exists();
        $hasChequeLine = $payment->relationLoaded('lines')
            ? $payment->lines->contains(fn ($line): bool => (string) $line->payment_method_type_snapshot === PaymentMethodType::Cheque->value)
            : $payment->lines()->where('payment_method_type_snapshot', PaymentMethodType::Cheque->value)->exists();

        return [
            'can_edit' => $this->reasons($document !== PaymentDocumentStatus::Draft, 'Only draft payments can be edited.'),
            'can_submit' => $this->reasons($document !== PaymentDocumentStatus::Draft, 'Only draft payments can be submitted.'),
            'can_approve' => $this->reasons($document !== PaymentDocumentStatus::Submitted, 'Only submitted payments can be approved.'),
            'can_post' => array_values(array_filter([
                $document !== PaymentDocumentStatus::Approved ? 'Only approved payments can be posted.' : null,
                $posting === PaymentPostingStatus::Posted || $hasPostingReference ? 'Payment is already posted.' : null,
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
                ! in_array($document, [PaymentDocumentStatus::Draft, PaymentDocumentStatus::Submitted, PaymentDocumentStatus::Approved], true)
                    ? 'Only unposted payment documents can be voided.' : null,
                $posting !== PaymentPostingStatus::NotPosted || $hasPostingReference ? 'Posted payments must be reversed, not voided.' : null,
                $hasActiveAllocations ? 'Allocated payments must be reversed before voiding.' : null,
            ])),
            'can_reverse' => array_values(array_filter([
                ! $posted ? 'Only posted payments can be reversed.' : null,
                $terminal ? 'Terminal payment documents cannot be reversed.' : null,
                $payment->reversals()->exists() ? 'Payment already has a reversal.' : null,
            ])),
            'can_settle' => $this->reasons($terminal, 'Terminal payment documents cannot be settled.'),
            'can_preview_cheque' => array_values(array_filter([
                ! $hasChequeLine ? 'Payment has no cheque-capable line.' : null,
                ! in_array($document, [PaymentDocumentStatus::Approved], true) ? 'Only approved payments can be previewed.' : null,
            ])),
            'can_print_cheque' => array_values(array_filter([
                ! $hasChequeLine ? 'Payment has no cheque-capable line.' : null,
                ! in_array($document, [PaymentDocumentStatus::Approved], true) ? 'Only approved payments can be printed.' : null,
            ])),
            'allocation_complete' => $allocation === PaymentAllocationState::FullyAllocated ? [] : ['Payment is not fully allocated.'],
        ];
    }

    private function reasons(bool $blocked, string $reason): array
    {
        return $blocked ? [$reason] : [];
    }

    private function stateValue(mixed $state): string
    {
        return $state instanceof \BackedEnum ? (string) $state->value : (string) $state;
    }

    private function documentStatus(Payment $payment): PaymentDocumentStatus
    {
        return $payment->document_status instanceof PaymentDocumentStatus
            ? $payment->document_status
            : PaymentDocumentStatus::from((string) $payment->document_status);
    }

    private function postingStatus(Payment $payment): PaymentPostingStatus
    {
        return $payment->posting_status instanceof PaymentPostingStatus
            ? $payment->posting_status
            : PaymentPostingStatus::from((string) $payment->posting_status);
    }
}
