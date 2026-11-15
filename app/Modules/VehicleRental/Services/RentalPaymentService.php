<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\DecimalMath;
use Modules\Invoice\Enums\InvoicePartyType;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\Invoice\Models\Invoice;
use Modules\Invoice\Services\InvoiceStatusService;
use Modules\Payment\Constants\PaymentPermission;
use Modules\Payment\DTOs\CreatePaymentData;
use Modules\Payment\DTOs\PaymentAllocationData;
use Modules\Payment\DTOs\PaymentLineData;
use Modules\Payment\DTOs\PaymentRefundData;
use Modules\Payment\DTOs\PaymentReversalData;
use Modules\Payment\Enums\AllocationStatus;
use Modules\Payment\Enums\PaymentDirection;
use Modules\Payment\Enums\PaymentDocumentStatus;
use Modules\Payment\Enums\PaymentPostingStatus;
use Modules\Payment\Enums\PaymentSourceType;
use Modules\Payment\Enums\PaymentType;
use Modules\Payment\Models\Payment;
use Modules\Payment\Models\PaymentAllocation;
use Modules\Payment\Models\PaymentRefund;
use Modules\Payment\Services\PaymentAllocationService;
use Modules\Payment\Services\PaymentCreationService;
use Modules\Payment\Services\PaymentDocumentLifecycleService;
use Modules\Payment\Services\PaymentPostingService;
use Modules\Payment\Services\PaymentReceiptService;
use Modules\Payment\Services\PaymentRefundWorkflowService;
use Modules\Payment\Services\PaymentReversalService;
use Modules\User\Services\UserAccessResolver;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\AgreementStatus;
use Modules\VehicleRental\Models\Agreement;
use Modules\VehicleRental\Models\BillingCycle;
use Modules\VehicleRental\Models\CustomerAgreement;
use Modules\VehicleRental\Models\OwnerAgreement;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class RentalPaymentService
{
    public function __construct(
        private readonly RentalAuthorization $authorization,
        private readonly UserAccessResolver $access,
        private readonly PaymentCreationService $payments,
        private readonly PaymentAllocationService $allocationService,
        private readonly PaymentDocumentLifecycleService $documentLifecycleService,
        private readonly PaymentPostingService $postingService,
        private readonly PaymentReversalService $reversalService,
        private readonly InvoiceStatusService $invoiceStatusService,
        private readonly PaymentReceiptService $receiptService,
        private readonly PaymentRefundWorkflowService $refundWorkflowService,
        private readonly DecimalMath $math,
    ) {}

    public function recordCustomerReceipt(
        AgreementContext $context,
        int $agreementId,
        int $expectedVersion,
        array $input,
        array $lines,
        array $allocations = []
    ): Payment {
        $this->authorize($context, AgreementKind::Customer, PaymentPermission::PAYMENTS_CREATE);

        return DB::transaction(function () use ($context, $agreementId, $expectedVersion, $input, $lines, $allocations): Payment {
            $agreement = CustomerAgreement::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->lockForUpdate()
                ->findOrFail($agreementId);

            if ($agreement->row_version !== $expectedVersion) {
                throw new ConflictHttpException('The agreement has been modified. Please reload before recording payment.');
            }
            if ($agreement->status !== AgreementStatus::Active) {
                throw ValidationException::withMessages(['agreement' => ['Payment can only be recorded against an active agreement.']]);
            }

            // Calculate total payment from lines
            $totalPayment = '0.000000';
            foreach ($lines as $line) {
                if ($line instanceof PaymentLineData) {
                    $totalPayment = $this->math->add($totalPayment, $line->amount, 6);
                }
            }

            if ($this->math->compare($totalPayment, '0.000000') <= 0) {
                throw ValidationException::withMessages(['lines' => ['Payment must have at least one method line with a positive amount.']]);
            }

            // Validate allocations
            $totalAllocated = '0.000000';
            $validatedInvoices = [];
            foreach ($allocations as $alloc) {
                $invId = (int) ($alloc['invoice_id'] ?? 0);
                $amt = (string) ($alloc['amount'] ?? '0.000000');
                if ($this->math->compare($amt, '0.000000') <= 0) {
                    continue;
                }

                $invoice = Invoice::query()
                    ->where('tenant_id', $context->tenantId)
                    ->where('organization_unit_id', $context->organizationUnitId)
                    ->lockForUpdate()
                    ->findOrFail($invId);

                if ($invoice->party_type !== InvoicePartyType::Customer->value || (int) $invoice->party_id !== (int) $agreement->customer_id) {
                    throw ValidationException::withMessages(['allocations' => ["Invoice {$invoice->invoice_number} does not belong to this customer."]]);
                }

                if ($invoice->status === InvoiceStatus::Draft) {
                    try {
                        $invoice = $this->invoiceStatusService->transition($invoice, InvoiceStatus::Approved, $context->actorId, 'Auto-approved for customer payment receipt');
                    } catch (\Throwable) {
                    }
                }

                if ($invoice->status === InvoiceStatus::Approved) {
                    try {
                        $invoice = $this->invoiceStatusService->transition($invoice, InvoiceStatus::Posted, $context->actorId, 'Auto-posted for customer payment receipt');
                    } catch (\Throwable) {
                    }
                }

                if ($invoice->status === InvoiceStatus::Draft) {
                    throw ValidationException::withMessages(['allocations' => ["Invoice {$invoice->invoice_number} must be approved and posted before applying payments."]]);
                }

                $rem = (string) ($invoice->balance?->remaining_amount ?? $invoice->balance_due ?? '0.000000');
                if ($this->math->compare($amt, $rem) > 0) {
                    throw ValidationException::withMessages(['allocations' => ["Allocation amount ({$amt}) exceeds open balance ({$rem}) on invoice {$invoice->invoice_number}."]]);
                }

                $totalAllocated = $this->math->add($totalAllocated, $amt, 6);
                $validatedInvoices[] = [
                    'invoice' => $invoice,
                    'amount' => $amt,
                ];
            }

            if ($this->math->compare($totalAllocated, $totalPayment) > 0) {
                throw ValidationException::withMessages(['allocations' => ['Total allocated amount cannot exceed total payment amount.']]);
            }

            $hasAllocations = ! empty($validatedInvoices);
            $paymentType = $hasAllocations ? PaymentType::CustomerReceipt : PaymentType::Advance;
            $sourceType = $hasAllocations ? PaymentSourceType::RentalCustomerPayment : PaymentSourceType::RentalAgreementAdvance;

            $payment = $this->payments->create(new CreatePaymentData(
                tenantId: $context->tenantId,
                paymentType: $paymentType,
                direction: PaymentDirection::Inbound,
                paymentDate: (string) $input['payment_date'],
                organizationUnitId: $context->organizationUnitId,
                partyType: 'customer',
                partyId: (int) $agreement->customer_id,
                sourceType: $sourceType->value,
                sourceId: $agreement->id,
                currencyId: $agreement->currency_id,
                exchangeRate: (string) ($input['exchange_rate'] ?? '1.000000'),
                referenceNumber: ! empty($input['reference_number']) ? trim((string) $input['reference_number']) : null,
                notes: ! empty($input['notes']) ? trim((string) $input['notes']) : "Payment receipt for rental agreement {$agreement->reference}",
                createdBy: $context->actorId,
                lines: $lines,
                metadata: [
                    'agreement_reference' => $agreement->reference,
                    'agreement_id' => $agreement->id,
                    'customer_id' => $agreement->customer_id,
                ],
                idempotencyKey: ! empty($input['idempotency_key']) ? trim((string) $input['idempotency_key']) : null,
            ));

            // Auto-approve and post payment
            if ($payment->document_status === PaymentDocumentStatus::Draft) {
                $payment = $this->documentLifecycleService->submit($payment, (int) $payment->row_version, $context->actorId);
            }
            if ($payment->document_status === PaymentDocumentStatus::Submitted) {
                $payment = $this->documentLifecycleService->approve($payment, (int) $payment->row_version, $context->actorId);
            }
            if ($payment->posting_status !== PaymentPostingStatus::Posted) {
                $payment = $this->postingService->post($payment, (int) $payment->row_version, $context->actorId);
            }

            // Apply allocations
            if ($hasAllocations) {
                $allocationDataList = [];
                foreach ($validatedInvoices as $item) {
                    $allocationDataList[] = new PaymentAllocationData(
                        invoiceId: (int) $item['invoice']->id,
                        allocatedAmount: $item['amount'],
                        allocationDate: (string) $input['payment_date'],
                        metadata: [
                            'source' => 'rental_agreement_payment',
                            'agreement_reference' => $agreement->reference,
                        ],
                    );
                }

                $payment = $this->allocationService->allocate(
                    $payment,
                    $allocationDataList,
                    (int) $payment->row_version,
                    $context->actorId
                );
            }

            return $payment->fresh([
                'currency',
                'lines.paymentMethod',
                'allocations',
                'unappliedBalance',
            ]);
        });
    }

    public function recordOwnerDisbursement(
        AgreementContext $context,
        int $agreementId,
        int $expectedVersion,
        array $input,
        array $lines,
        array $allocations = []
    ): Payment {
        $this->authorize($context, AgreementKind::Owner, PaymentPermission::PAYMENTS_CREATE);

        return DB::transaction(function () use ($context, $agreementId, $expectedVersion, $input, $lines, $allocations): Payment {
            $agreement = OwnerAgreement::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->lockForUpdate()
                ->findOrFail($agreementId);

            if ($agreement->row_version !== $expectedVersion) {
                throw new ConflictHttpException('The owner agreement has been modified. Please reload before recording payment.');
            }
            if ($agreement->status !== AgreementStatus::Active) {
                throw ValidationException::withMessages(['agreement' => ['Payment can only be disbursed against an active owner agreement.']]);
            }

            $totalPayment = '0.000000';
            foreach ($lines as $line) {
                if ($line instanceof PaymentLineData) {
                    $totalPayment = $this->math->add($totalPayment, $line->amount, 6);
                }
            }

            if ($this->math->compare($totalPayment, '0.000000') <= 0) {
                throw ValidationException::withMessages(['lines' => ['Disbursement must have at least one method line with a positive amount.']]);
            }

            $totalAllocated = '0.000000';
            $validatedBills = [];
            foreach ($allocations as $alloc) {
                $billId = (int) ($alloc['invoice_id'] ?? 0);
                $amt = (string) ($alloc['amount'] ?? '0.000000');
                if ($this->math->compare($amt, '0.000000') <= 0) {
                    continue;
                }

                $bill = Invoice::query()
                    ->where('tenant_id', $context->tenantId)
                    ->where('organization_unit_id', $context->organizationUnitId)
                    ->lockForUpdate()
                    ->findOrFail($billId);

                if ($bill->party_type !== InvoicePartyType::Supplier->value || (int) $bill->party_id !== (int) $agreement->supplier_id) {
                    throw ValidationException::withMessages(['allocations' => ["Bill {$bill->invoice_number} does not belong to this vehicle owner/supplier."]]);
                }

                if ($bill->status === InvoiceStatus::Draft) {
                    try {
                        $bill = $this->invoiceStatusService->transition($bill, InvoiceStatus::Approved, $context->actorId, 'Auto-approved for owner disbursement');
                    } catch (\Throwable) {
                    }
                }

                if ($bill->status === InvoiceStatus::Approved) {
                    try {
                        $bill = $this->invoiceStatusService->transition($bill, InvoiceStatus::Posted, $context->actorId, 'Auto-posted for owner disbursement');
                    } catch (\Throwable) {
                    }
                }

                if ($bill->status === InvoiceStatus::Draft) {
                    throw ValidationException::withMessages(['allocations' => ["Bill {$bill->invoice_number} must be approved and posted before disbursing payment."]]);
                }

                $rem = (string) ($bill->balance?->remaining_amount ?? $bill->balance_due ?? '0.000000');
                if ($this->math->compare($amt, $rem) > 0) {
                    throw ValidationException::withMessages(['allocations' => ["Disbursement amount ({$amt}) exceeds open balance ({$rem}) on bill {$bill->invoice_number}."]]);
                }

                $totalAllocated = $this->math->add($totalAllocated, $amt, 6);
                $validatedBills[] = [
                    'bill' => $bill,
                    'amount' => $amt,
                ];
            }

            if ($this->math->compare($totalAllocated, $totalPayment) > 0) {
                throw ValidationException::withMessages(['allocations' => ['Total allocated amount cannot exceed total disbursement amount.']]);
            }

            $payment = $this->payments->create(new CreatePaymentData(
                tenantId: $context->tenantId,
                paymentType: PaymentType::SupplierPayment,
                direction: PaymentDirection::Outbound,
                paymentDate: (string) $input['payment_date'],
                organizationUnitId: $context->organizationUnitId,
                partyType: 'supplier',
                partyId: (int) $agreement->supplier_id,
                sourceType: PaymentSourceType::RentalOwnerPayment->value,
                sourceId: $agreement->id,
                currencyId: $agreement->currency_id,
                exchangeRate: (string) ($input['exchange_rate'] ?? '1.000000'),
                referenceNumber: ! empty($input['reference_number']) ? trim((string) $input['reference_number']) : null,
                notes: ! empty($input['notes']) ? trim((string) $input['notes']) : "Disbursement voucher for owner agreement {$agreement->reference}",
                createdBy: $context->actorId,
                lines: $lines,
                metadata: [
                    'agreement_reference' => $agreement->reference,
                    'agreement_id' => $agreement->id,
                    'supplier_id' => $agreement->supplier_id,
                ],
                idempotencyKey: ! empty($input['idempotency_key']) ? trim((string) $input['idempotency_key']) : null,
            ));

            if ($payment->document_status === PaymentDocumentStatus::Draft) {
                $payment = $this->documentLifecycleService->submit($payment, (int) $payment->row_version, $context->actorId);
            }
            if ($payment->document_status === PaymentDocumentStatus::Submitted) {
                $payment = $this->documentLifecycleService->approve($payment, (int) $payment->row_version, $context->actorId);
            }
            if ($payment->posting_status !== PaymentPostingStatus::Posted) {
                $payment = $this->postingService->post($payment, (int) $payment->row_version, $context->actorId);
            }

            if (! empty($validatedBills)) {
                $allocationDataList = [];
                foreach ($validatedBills as $item) {
                    $allocationDataList[] = new PaymentAllocationData(
                        invoiceId: (int) $item['bill']->id,
                        allocatedAmount: $item['amount'],
                        allocationDate: (string) $input['payment_date'],
                        metadata: [
                            'source' => 'rental_owner_disbursement',
                            'agreement_reference' => $agreement->reference,
                        ],
                    );
                }

                $payment = $this->allocationService->allocate(
                    $payment,
                    $allocationDataList,
                    (int) $payment->row_version,
                    $context->actorId
                );
            }

            return $payment->fresh([
                'currency',
                'lines.paymentMethod',
                'allocations',
                'unappliedBalance',
            ]);
        });
    }

    public function allocateUnappliedFunds(
        AgreementContext $context,
        int $agreementId,
        int $paymentId,
        array $allocations,
        string $allocationDate
    ): Payment {
        $this->authorize($context, AgreementKind::Customer, PaymentPermission::PAYMENTS_ALLOCATE);

        return DB::transaction(function () use ($context, $agreementId, $paymentId, $allocations, $allocationDate): Payment {
            $payment = Payment::query()
                ->where('tenant_id', $context->tenantId)
                ->where('organization_unit_id', $context->organizationUnitId)
                ->lockForUpdate()
                ->findOrFail($paymentId);

            $unapplied = (string) ($payment->unapplied_amount ?? $payment->unappliedBalance?->unapplied_amount ?? '0.000000');
            if ($this->math->compare($unapplied, '0.000000') <= 0) {
                throw ValidationException::withMessages(['payment_id' => ['This payment has no unapplied balance available for allocation.']]);
            }

            $totalRequested = '0.000000';
            $allocationDataList = [];
            foreach ($allocations as $item) {
                $invId = (int) ($item['invoice_id'] ?? 0);
                $amt = (string) ($item['amount'] ?? '0.000000');
                if ($this->math->compare($amt, '0.000000') <= 0) {
                    continue;
                }

                $invoice = Invoice::query()
                    ->where('tenant_id', $context->tenantId)
                    ->where('organization_unit_id', $context->organizationUnitId)
                    ->lockForUpdate()
                    ->findOrFail($invId);

                if ($invoice->status === InvoiceStatus::Draft) {
                    try {
                        $invoice = $this->invoiceStatusService->transition($invoice, InvoiceStatus::Approved, $context->actorId, 'Auto-approved for unapplied payment allocation');
                    } catch (\Throwable) {
                    }
                }

                if ($invoice->status === InvoiceStatus::Approved) {
                    try {
                        $invoice = $this->invoiceStatusService->transition($invoice, InvoiceStatus::Posted, $context->actorId, 'Auto-posted for unapplied payment allocation');
                    } catch (\Throwable) {
                    }
                }

                $rem = (string) ($invoice->balance?->remaining_amount ?? $invoice->balance_due ?? '0.000000');
                if ($this->math->compare($amt, $rem) > 0) {
                    throw ValidationException::withMessages(['amount' => ["Allocation amount ({$amt}) exceeds open balance ({$rem}) on invoice {$invoice->invoice_number}."]]);
                }

                $totalRequested = $this->math->add($totalRequested, $amt, 6);
                $allocationDataList[] = new PaymentAllocationData(
                    invoiceId: $invId,
                    allocatedAmount: $amt,
                    allocationDate: $allocationDate,
                    metadata: ['source' => 'unapplied_advance_allocation'],
                );
            }

            if ($this->math->compare($totalRequested, $unapplied) > 0) {
                throw ValidationException::withMessages(['amount' => ["Total allocation ({$totalRequested}) exceeds available unapplied balance ({$unapplied})."]]);
            }

            return $this->allocationService->allocate(
                $payment,
                $allocationDataList,
                (int) $payment->row_version,
                $context->actorId
            );
        });
    }

    public function reversePayment(
        AgreementContext $context,
        int $agreementId,
        int $paymentId,
        string $reason,
        string $reversalDate
    ): Payment {
        $this->authorize($context, AgreementKind::Customer, PaymentPermission::PAYMENTS_REVERSE);

        return DB::transaction(function () use ($context, $paymentId, $reason, $reversalDate): Payment {
            $payment = Payment::query()
                ->where('tenant_id', $context->tenantId)
                ->where('organization_unit_id', $context->organizationUnitId)
                ->lockForUpdate()
                ->findOrFail($paymentId);

            $this->reversalService->reverse(new PaymentReversalData(
                paymentId: $payment->id,
                expectedVersion: (int) $payment->row_version,
                reversalDate: $reversalDate,
                reason: $reason,
                reversedBy: $context->actorId,
            ));

            return $payment->fresh(['allocations', 'unappliedBalance']);
        });
    }

    public function refundPayment(
        AgreementContext $context,
        AgreementKind $kind,
        int $agreementId,
        int $paymentId,
        array $input
    ): PaymentRefund {
        $this->authorize($context, $kind, PaymentPermission::PAYMENTS_REFUND);

        return DB::transaction(function () use ($context, $kind, $agreementId, $paymentId, $input): PaymentRefund {
            $agreement = $kind === AgreementKind::Customer
                ? CustomerAgreement::query()->forContext($context->tenantId, $context->organizationUnitId)->findOrFail($agreementId)
                : OwnerAgreement::query()->forContext($context->tenantId, $context->organizationUnitId)->findOrFail($agreementId);

            $payment = Payment::query()
                ->where('tenant_id', $context->tenantId)
                ->where('organization_unit_id', $context->organizationUnitId)
                ->lockForUpdate()
                ->findOrFail($paymentId);

            $amount = $this->math->normalize((string) ($input['amount'] ?? '0.000000'));
            if ($this->math->isNegative($amount) || $this->math->isZero($amount)) {
                throw ValidationException::withMessages(['amount' => ['Refund amount must be greater than zero.']]);
            }

            $unapplied = (string) ($payment->unapplied_amount ?? $payment->unappliedBalance?->unapplied_amount ?? '0.000000');
            if ($this->math->compare($amount, $unapplied) > 0) {
                throw ValidationException::withMessages(['amount' => ["Refund amount ({$amount}) cannot exceed available unapplied amount ({$unapplied})."]]);
            }

            $paymentMethodId = (int) ($input['payment_method_id'] ?? 0);
            if ($paymentMethodId < 1) {
                throw ValidationException::withMessages(['payment_method_id' => ['A valid refund payment method is required.']]);
            }

            $refundDate = (string) ($input['refund_date'] ?? now()->toDateString());
            $expectedVersion = (int) ($input['expected_version'] ?? $payment->row_version);

            $refundData = new PaymentRefundData(
                paymentId: $payment->id,
                expectedVersion: $expectedVersion,
                refundDate: $refundDate,
                amount: $amount,
                paymentMethodId: $paymentMethodId,
                exchangeRate: (string) ($input['exchange_rate'] ?? $payment->exchange_rate ?? '1.000000'),
                referenceNumber: ! empty($input['reference_number']) ? trim((string) $input['reference_number']) : null,
                externalBankName: ! empty($input['external_bank_name']) ? trim((string) $input['external_bank_name']) : null,
                externalBankBranch: ! empty($input['external_bank_branch']) ? trim((string) $input['external_bank_branch']) : null,
                instrumentNumber: ! empty($input['instrument_number']) ? trim((string) $input['instrument_number']) : null,
                instrumentDate: ! empty($input['instrument_date']) ? (string) $input['instrument_date'] : null,
                reason: ! empty($input['reason']) ? trim((string) $input['reason']) : "Refund for payment {$payment->payment_number} on {$agreement->reference}",
                refundedBy: $context->actorId,
            );

            $refund = $this->refundWorkflowService->refund($refundData);

            // Auto-approve and post the generated outbound/inbound refund payment
            $refundPayment = $refund->refundPayment;
            if ($refundPayment) {
                if ($refundPayment->document_status === PaymentDocumentStatus::Draft) {
                    $refundPayment = $this->documentLifecycleService->submit($refundPayment, (int) $refundPayment->row_version, $context->actorId);
                }
                if ($refundPayment->document_status === PaymentDocumentStatus::Submitted) {
                    $refundPayment = $this->documentLifecycleService->approve($refundPayment, (int) $refundPayment->row_version, $context->actorId);
                }
                if ($refundPayment->posting_status !== PaymentPostingStatus::Posted) {
                    $refundPayment = $this->postingService->post($refundPayment, (int) $refundPayment->row_version, $context->actorId);
                }
            }

            return $refund->fresh(['refundPayment.lines', 'payment']);
        });
    }

    public function listAgreementPayments(
        AgreementContext $context,
        int $agreementId,
        AgreementKind $kind
    ): array {
        $this->authorize($context, $kind, PaymentPermission::PAYMENTS_VIEW);

        $agreement = $kind === AgreementKind::Customer
            ? CustomerAgreement::query()->forContext($context->tenantId, $context->organizationUnitId)->findOrFail($agreementId)
            : OwnerAgreement::query()->forContext($context->tenantId, $context->organizationUnitId)->findOrFail($agreementId);

        // 1. Direct payments with source_id = agreementId
        $directPayments = Payment::query()
            ->where('tenant_id', $context->tenantId)
            ->where('organization_unit_id', $context->organizationUnitId)
            ->whereIn('source_type', [
                PaymentSourceType::RentalCustomerPayment->value,
                PaymentSourceType::RentalOwnerPayment->value,
                PaymentSourceType::RentalAgreementAdvance->value,
                PaymentSourceType::RentalAgreementDeposit->value,
            ])
            ->where('source_id', $agreement->id)
            ->with(['lines.paymentMethod', 'allocations', 'unappliedBalance'])
            ->get();

        // 2. Invoices linked to this agreement
        $foreignKey = $kind === AgreementKind::Customer ? 'customer_agreement_id' : 'owner_agreement_id';
        $cycleInvoiceIds = BillingCycle::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->where($foreignKey, $agreement->id)
            ->pluck('final_invoice_id')
            ->merge(
                BillingCycle::query()
                    ->forContext($context->tenantId, $context->organizationUnitId)
                    ->where($foreignKey, $agreement->id)
                    ->pluck('initial_invoice_id')
            )
            ->merge(
                BillingCycle::query()
                    ->forContext($context->tenantId, $context->organizationUnitId)
                    ->where($foreignKey, $agreement->id)
                    ->pluck('supplementary_invoice_id')
            )
            ->filter()
            ->all();

        $referencedInvoiceIds = Invoice::query()
            ->where('tenant_id', $context->tenantId)
            ->where('organization_unit_id', $context->organizationUnitId)
            ->where(function ($q) use ($agreement) {
                $q->where('source_document_number', $agreement->reference)
                    ->orWhere('source_document_number', 'like', $agreement->reference.'-%');
            })
            ->pluck('id')
            ->all();

        $allInvoiceIds = array_unique(array_merge($cycleInvoiceIds, $referencedInvoiceIds));

        // 3. Payments allocated to these invoices
        $allocatedPaymentIds = PaymentAllocation::query()
            ->where('tenant_id', $context->tenantId)
            ->where('organization_unit_id', $context->organizationUnitId)
            ->whereIn('invoice_id', $allInvoiceIds)
            ->where('status', AllocationStatus::Active->value)
            ->pluck('payment_id')
            ->all();

        $allocatedPayments = Payment::query()
            ->where('tenant_id', $context->tenantId)
            ->where('organization_unit_id', $context->organizationUnitId)
            ->whereIn('id', $allocatedPaymentIds)
            ->with(['lines.paymentMethod', 'allocations', 'unappliedBalance'])
            ->get();

        // 4. Linked refunds originating from these agreement payments
        $originalPaymentIds = $directPayments->pluck('id')->merge($allocatedPayments->pluck('id'))->unique()->filter()->all();
        $refundPayments = ! empty($originalPaymentIds)
            ? Payment::query()
                ->where('tenant_id', $context->tenantId)
                ->where('organization_unit_id', $context->organizationUnitId)
                ->where('payment_type', PaymentType::Refund->value)
                ->whereIn('original_payment_id', $originalPaymentIds)
                ->with(['lines.paymentMethod', 'allocations', 'unappliedBalance'])
                ->get()
            : collect();

        $merged = $directPayments->concat($allocatedPayments)->concat($refundPayments)->unique('id')->sortByDesc('payment_date');

        return $merged->map(function (Payment $p) {
            $methods = $p->lines->map(fn ($l) => [
                'id' => $l->id,
                'method_name' => $l->paymentMethod?->name ?? 'Payment Method',
                'method_type' => $l->paymentMethod?->method_type instanceof \BackedEnum
                    ? $l->paymentMethod->method_type->value
                    : (string) ($l->paymentMethod?->method_type ?? 'other'),
                'amount' => (string) $l->amount,
                'reference' => $l->reference_number,
            ])->all();

            $allocs = $p->allocations->map(fn ($a) => [
                'id' => $a->id,
                'invoice_id' => $a->invoice_id,
                'invoice_number' => $a->invoice_number_snapshot ?? "INV-#{$a->invoice_id}",
                'allocated_amount' => (string) $a->allocated_amount,
                'status' => $a->status,
            ])->all();

            return [
                'id' => $p->id,
                'payment_number' => $p->payment_number,
                'payment_date' => $p->payment_date->toDateString(),
                'payment_type' => $p->payment_type instanceof \BackedEnum ? $p->payment_type->value : (string) $p->payment_type,
                'direction' => $p->direction instanceof \BackedEnum ? $p->direction->value : (string) $p->direction,
                'document_status' => $p->document_status instanceof \BackedEnum ? $p->document_status->value : (string) $p->document_status,
                'posting_status' => $p->posting_status instanceof \BackedEnum ? $p->posting_status->value : (string) $p->posting_status,
                'total_amount' => (string) $p->total_amount,
                'allocated_amount' => (string) $p->allocated_amount,
                'unapplied_amount' => (string) ($p->unapplied_amount ?? $p->unappliedBalance?->unapplied_amount ?? '0.000000'),
                'reference_number' => $p->reference_number,
                'notes' => $p->notes,
                'row_version' => (int) $p->row_version,
                'original_payment_id' => $p->original_payment_id,
                'methods' => $methods,
                'allocations' => $allocs,
            ];
        })->values()->all();
    }

    private function authorize(AgreementContext $context, AgreementKind $kind, string $permission): void
    {
        $this->authorization->assert($context, $kind, false);
        if (! $this->access->can($context->actorId, $context->tenantId, $permission)) {
            throw new AuthorizationException("This action requires permission: {$permission}");
        }
    }
}
