<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\CreatePaymentAllocationServiceInterface;
use Modules\Finance\Application\Contracts\CreatePaymentServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentRepositoryInterface;
use Modules\Purchase\Application\Contracts\RecordPurchasePaymentServiceInterface;
use Modules\Purchase\Application\DTOs\RecordPurchasePaymentData;
use Modules\Purchase\Domain\Entities\PurchaseInvoice;
use Modules\Purchase\Domain\Events\PurchasePaymentRecorded;
use Modules\Purchase\Domain\Exceptions\PurchaseInvoiceNotFoundException;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseInvoiceRepositoryInterface;

class RecordPurchasePaymentService extends BaseService implements RecordPurchasePaymentServiceInterface
{
    public function __construct(
        private readonly PurchaseInvoiceRepositoryInterface $invoiceRepository,
        private readonly CreatePaymentServiceInterface $createPaymentService,
        private readonly CreatePaymentAllocationServiceInterface $createPaymentAllocationService,
        private readonly PaymentRepositoryInterface $paymentRepository,
    ) {
        parent::__construct($invoiceRepository);
    }

    protected function handle(array $data): PurchaseInvoice
    {
        $dto = RecordPurchasePaymentData::fromArray($data);

        $invoice = $this->invoiceRepository->find($dto->invoiceId);

        if (! $invoice) {
            throw new PurchaseInvoiceNotFoundException($dto->invoiceId);
        }

        $existingPayment = null;
        if ($dto->idempotencyKey !== null && $dto->idempotencyKey !== '') {
            $existingPayment = $this->paymentRepository->findByTenantAndIdempotencyKey($dto->tenantId, $dto->idempotencyKey);
            if ($existingPayment !== null && $this->hasActiveAllocation(
                (int) $dto->tenantId,
                (int) $existingPayment->getId(),
                'purchase_invoice',
                (int) $invoice->getId(),
            )) {
                return $invoice;
            }
        }

        if (! in_array($invoice->getStatus(), ['approved', 'partial_paid'], true)) {
            throw new \InvalidArgumentException('Payment can only be recorded against approved or partially paid invoices.');
        }

        $balanceDue = $invoice->getBalanceDue();
        if (bccomp((string) $dto->amount, '0.000000', 6) <= 0) {
            throw new \InvalidArgumentException('Payment amount must be greater than zero.');
        }

        if (bccomp((string) $dto->amount, $balanceDue, 6) > 0) {
            throw new \InvalidArgumentException(
                sprintf('Payment amount %.6f exceeds balance due %.6f.', (float) $dto->amount, (float) $balanceDue)
            );
        }

        return DB::transaction(function () use ($dto, $invoice): PurchaseInvoice {
            $payment = $this->createPaymentService->execute([
                'tenant_id' => $dto->tenantId,
                'payment_number' => $dto->paymentNumber,
                'direction' => 'outbound',
                'party_type' => 'supplier',
                'party_id' => $invoice->getSupplierId(),
                'payment_method_id' => $dto->paymentMethodId,
                'account_id' => $dto->accountId,
                'amount' => (float) $dto->amount,
                'currency_id' => $dto->currencyId,
                'payment_date' => $dto->paymentDate,
                'exchange_rate' => $dto->exchangeRate,
                'reference' => $dto->reference,
                'notes' => $dto->notes,
                'status' => 'posted',
                'idempotency_key' => $dto->idempotencyKey,
            ]);

            if ($this->hasActiveAllocation(
                (int) $dto->tenantId,
                (int) $payment->getId(),
                'purchase_invoice',
                (int) $invoice->getId(),
            )) {
                return $this->invoiceRepository->find((int) $invoice->getId()) ?? $invoice;
            }

            $this->createPaymentAllocationService->execute([
                'payment_id' => $payment->getId(),
                'invoice_type' => 'purchase_invoice',
                'invoice_id' => $invoice->getId(),
                'allocated_amount' => (float) $dto->amount,
                'tenant_id' => $dto->tenantId,
            ]);

            $invoice->recordPayment((string) $dto->amount);

            $saved = $this->invoiceRepository->save($invoice);

            $this->addEvent(new PurchasePaymentRecorded(
                tenantId: $dto->tenantId,
                purchaseInvoiceId: (int) $saved->getId(),
                supplierId: $saved->getSupplierId(),
                paymentId: (int) $payment->getId(),
                apAccountId: $saved->getApAccountId(),
                cashAccountId: $dto->accountId,
                amount: (string) $dto->amount,
                currencyId: $dto->currencyId,
                exchangeRate: (string) $dto->exchangeRate,
                paymentDate: $dto->paymentDate,
                createdBy: (int) (Auth::id() ?? 0),
            ));

            return $saved;
        });
    }

    private function hasActiveAllocation(int $tenantId, int $paymentId, string $invoiceType, int $invoiceId): bool
    {
        return DB::table('payment_allocations')
            ->where('tenant_id', $tenantId)
            ->where('payment_id', $paymentId)
            ->where('invoice_type', $invoiceType)
            ->where('invoice_id', $invoiceId)
            ->whereNull('deleted_at')
            ->exists();
    }
}
