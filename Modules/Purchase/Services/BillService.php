<?php

declare(strict_types=1);

namespace Modules\Purchase\Services;

use Modules\Core\Helpers\MathHelper;
use Modules\Core\Helpers\TransactionHelper;
use Modules\Core\Services\CodeGeneratorService;
use Modules\Core\Services\TotalCalculationService;
use Modules\Purchase\Enums\BillStatus;
use Modules\Purchase\Events\BillCreated;
use Modules\Purchase\Events\BillPaymentRecorded;
use Modules\Purchase\Events\BillSent;
use Modules\Purchase\Exceptions\InvalidPurchaseOrderStatusException;
use Modules\Purchase\Models\Bill;
use Modules\Purchase\Models\BillPayment;
use Modules\Purchase\Models\Vendor;
use Modules\Purchase\Repositories\BillRepository;
use Modules\Purchase\Repositories\GoodsReceiptRepository;
use Modules\Purchase\Repositories\PurchaseOrderRepository;
use Modules\Purchase\Repositories\VendorRepository;

/**
 * Bill Service
 *
 * Handles business logic for vendor bills including creation,
 * payment recording, and vendor balance management.
 */
class BillService
{
    public function __construct(
        private BillRepository $billRepository,
        private PurchaseOrderRepository $purchaseOrderRepository,
        private GoodsReceiptRepository $goodsReceiptRepository,
        private VendorRepository $vendorRepository,
        private CodeGeneratorService $codeGenerator,
        private TotalCalculationService $totalCalculationService
    ) {}

    /**
     * Create a new bill with items.
     */
    public function create(array $data, array $items = []): Bill
    {
        return TransactionHelper::execute(function () use ($data, $items) {
            // Generate bill code if not provided
            if (empty($data['bill_code'])) {
                $data['bill_code'] = $this->generateBillCode();
            }

            // Set default status and dates
            $data['status'] = $data['status'] ?? BillStatus::DRAFT;
            $data['bill_date'] = $data['bill_date'] ?? now();
            $data['paid_amount'] = $data['paid_amount'] ?? '0.00';

            // Calculate due date if not provided
            if (empty($data['due_date'])) {
                $vendor = $this->vendorRepository->findOrFail($data['vendor_id']);
                $paymentTermsDays = $vendor->payment_terms_days ?? config('purchase.bill.default_payment_terms_days', 30);
                $data['due_date'] = now()->addDays($paymentTermsDays);
            }

            // Calculate totals if items are provided
            if (! empty($items)) {
                $totals = $this->calculateTotals($items, $data);
                $data = array_merge($data, $totals);
            }

            // Create bill
            $bill = $this->billRepository->create($data);

            // Create items if provided
            if (! empty($items)) {
                foreach ($items as $item) {
                    $item['bill_id'] = $bill->id;
                    $bill->items()->create($item);
                }
                $bill->load('items');
            }

            // Fire event
            event(new BillCreated($bill));

            return $bill;
        });
    }

    /**
     * Update bill and recalculate totals.
     */
    public function update(string $id, array $data, ?array $items = null): Bill
    {
        $bill = $this->billRepository->findOrFail($id);

        if ($bill->status !== BillStatus::DRAFT) {
            throw new InvalidPurchaseOrderStatusException(
                "Bill cannot be modified in {$bill->status->value} status"
            );
        }

        return TransactionHelper::execute(function () use ($bill, $data, $items) {
            // Update items if provided
            if ($items !== null) {
                // Delete existing items
                $bill->items()->delete();

                // Create new items
                foreach ($items as $item) {
                    $item['bill_id'] = $bill->id;
                    $bill->items()->create($item);
                }

                // Recalculate totals
                $totals = $this->calculateTotals($items, $data);
                $data = array_merge($data, $totals);
            }

            // Update bill
            return $this->billRepository->update($bill->id, $data);
        });
    }

    /**
     * Send bill (mark as open).
     */
    public function send(string $id): Bill
    {
        $bill = $this->billRepository->findOrFail($id);

        if ($bill->status !== BillStatus::DRAFT) {
            throw new InvalidPurchaseOrderStatusException(
                'Only draft bills can be sent'
            );
        }

        $bill = $this->billRepository->update($bill->id, [
            'status' => BillStatus::SENT,
            'sent_at' => now(),
        ]);

        // Update vendor balance
        $this->updateVendorBalance($bill->vendor);

        // Fire event
        event(new BillSent($bill));

        return $bill;
    }

    /**
     * Record payment against a bill.
     */
    public function recordPayment(string $billId, array $paymentData): BillPayment
    {
        $bill = $this->billRepository->findOrFail($billId);

        if ($bill->status === BillStatus::DRAFT) {
            throw new InvalidPurchaseOrderStatusException(
                'Cannot record payment for draft bill'
            );
        }

        if ($bill->status === BillStatus::PAID) {
            throw new InvalidPurchaseOrderStatusException(
                'Bill is already fully paid'
            );
        }

        if ($bill->status === BillStatus::CANCELLED) {
            throw new InvalidPurchaseOrderStatusException(
                'Cannot record payment for cancelled bill'
            );
        }

        return TransactionHelper::execute(function () use ($bill, $paymentData) {
            $paymentAmount = (string) $paymentData['amount'];

            // Validate payment amount
            $outstanding = MathHelper::subtract(
                (string) $bill->total_amount,
                (string) $bill->paid_amount
            );

            if (MathHelper::compare($paymentAmount, '0') <= 0) {
                throw new InvalidPurchaseOrderStatusException(
                    'Payment amount must be greater than zero'
                );
            }

            if (MathHelper::compare($paymentAmount, $outstanding) > 0) {
                throw new InvalidPurchaseOrderStatusException(
                    'Payment amount exceeds outstanding balance'
                );
            }

            // Create payment record
            $payment = $bill->payments()->create(array_merge($paymentData, [
                'vendor_id' => $bill->vendor_id,
                'payment_date' => $paymentData['payment_date'] ?? now(),
            ]));

            // Update bill paid amount
            $newPaidAmount = MathHelper::add((string) $bill->paid_amount, $paymentAmount);
            $bill->paid_amount = $newPaidAmount;

            // Update bill status
            $this->updateBillStatus($bill);

            // Update vendor balance
            $this->updateVendorBalance($bill->vendor);

            // Fire event
            event(new BillPaymentRecorded($payment));

            return $payment;
        });
    }

    /**
     * Cancel bill.
     */
    public function cancel(string $id, ?string $reason = null): Bill
    {
        $bill = $this->billRepository->findOrFail($id);

        if ($bill->status === BillStatus::PAID) {
            throw new InvalidPurchaseOrderStatusException(
                'Cannot cancel fully paid bill'
            );
        }

        if ($bill->status === BillStatus::CANCELLED) {
            throw new InvalidPurchaseOrderStatusException(
                'Bill is already cancelled'
            );
        }

        return TransactionHelper::execute(function () use ($bill, $reason) {
            $bill = $this->billRepository->update($bill->id, [
                'status' => BillStatus::CANCELLED,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            // Update vendor balance
            $this->updateVendorBalance($bill->vendor);

            return $bill;
        });
    }

    /**
     * Delete bill.
     */
    public function delete(string $id): bool
    {
        $bill = $this->billRepository->findOrFail($id);

        return $this->billRepository->delete($bill->id);
    }

    /**
     * Calculate totals for bill items.
     */
    private function calculateTotals(array $items, array $data): array
    {
        return $this->totalCalculationService->calculateLineTotals($items, $data);
    }

    /**
     * Update bill status based on payment.
     */
    public function updateBillStatus(Bill $bill): void
    {
        $comparison = MathHelper::compare(
            (string) $bill->paid_amount,
            (string) $bill->total_amount
        );

        if ($comparison >= 0) {
            $bill->status = BillStatus::PAID;
            $bill->paid_at = now();
        } elseif (MathHelper::compare((string) $bill->paid_amount, '0') > 0) {
            $bill->status = BillStatus::PARTIALLY_PAID;
        } else {
            $bill->status = BillStatus::UNPAID;
        }

        // Check if overdue
        if (
            $bill->status !== BillStatus::PAID &&
            $bill->status !== BillStatus::CANCELLED &&
            $bill->due_date < now()
        ) {
            $bill->overdue_at = $bill->overdue_at ?? now();
        }

        $bill->save();
    }

    /**
     * Update vendor current balance.
     */
    public function updateVendorBalance(Vendor $vendor): void
    {
        // Calculate total outstanding from all unpaid bills
        $outstanding = $this->billRepository->getModel()
            ->where('vendor_id', $vendor->id)
            ->whereIn('status', [BillStatus::SENT, BillStatus::UNPAID, BillStatus::PARTIALLY_PAID, BillStatus::OVERDUE])
            ->selectRaw('SUM(total_amount - paid_amount) as total')
            ->value('total');

        $vendor->current_balance = $outstanding ?? '0.000000';
        $vendor->save();
    }

    /**
     * Generate unique bill code.
     */
    private function generateBillCode(): string
    {
        $prefix = config('purchase.bill.code_prefix', 'BILL-');

        return $this->codeGenerator->generateDateBased(
            $prefix,
            'Ymd',
            null,
            6,
            fn (string $code) => $this->billRepository->findByBillCode($code) !== null
        );
    }
}
