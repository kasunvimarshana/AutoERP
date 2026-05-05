<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\CreatePaymentAllocationServiceInterface;
use Modules\Finance\Application\Contracts\CreatePaymentServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentRepositoryInterface;
use Modules\Purchase\Application\Contracts\RecordPurchaseRefundServiceInterface;
use Modules\Purchase\Application\DTOs\RecordPurchaseRefundData;
use Modules\Purchase\Domain\Entities\PurchaseInvoice;
use Modules\Purchase\Domain\Events\PurchaseRefundRecorded;
use Modules\Purchase\Domain\Exceptions\PurchaseInvoiceNotFoundException;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseInvoiceRepositoryInterface;

class RecordPurchaseRefundService extends BaseService implements RecordPurchaseRefundServiceInterface
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
        $dto = RecordPurchaseRefundData::fromArray($data);

        $invoice = $this->invoiceRepository->find($dto->invoiceId);

        if (! $invoice) {
            throw new PurchaseInvoiceNotFoundException($dto->invoiceId);
        }

        if (! in_array($invoice->getStatus(), ['paid', 'partial_paid'], true)) {
            throw new \InvalidArgumentException('Refund can only be recorded against paid or partially paid invoices.');
        }

        $paidAmount = $invoice->getPaidAmount();

        if (bccomp((string) $dto->amount, '0.000000', 6) <= 0) {
            throw new \InvalidArgumentException('Refund amount must be greater than zero.');
        }

        if (bccomp((string) $dto->amount, $paidAmount, 6) > 0) {
            throw new \InvalidArgumentException(
                sprintf('Refund amount %.6f exceeds paid amount %.6f.', (float) $dto->amount, (float) $paidAmount)
            );
        }

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

        return DB::transaction(function () use ($dto, $invoice): PurchaseInvoice {
            $refundPayment = $this->createPaymentService->execute([
                'tenant_id' => $dto->tenantId,
                'payment_number' => $dto->refundNumber,
                'direction' => 'inbound',
                'party_type' => 'supplier',
                'party_id' => $invoice->getSupplierId(),
                'payment_method_id' => $dto->paymentMethodId,
                'account_id' => $dto->accountId,
                'amount' => (float) $dto->amount,
                'currency_id' => $dto->currencyId,
                'payment_date' => $dto->refundDate,
                'exchange_rate' => $dto->exchangeRate,
                'reference' => $dto->reference,
                'notes' => $dto->notes,
                'status' => 'posted',
                'idempotency_key' => $dto->idempotencyKey,
            ]);

            if ($this->hasActiveAllocation(
                (int) $dto->tenantId,
                (int) $refundPayment->getId(),
                'purchase_invoice',
                (int) $invoice->getId(),
            )) {
                return $this->invoiceRepository->find((int) $invoice->getId()) ?? $invoice;
            }

            $this->createPaymentAllocationService->execute([
                'payment_id' => $refundPayment->getId(),
                'invoice_type' => 'purchase_invoice',
                'invoice_id' => $invoice->getId(),
                'allocated_amount' => (float) $dto->amount,
                'tenant_id' => $dto->tenantId,
            ]);

            $invoice->recordRefund((string) $dto->amount);

            $saved = $this->invoiceRepository->save($invoice);

            $this->addEvent(new PurchaseRefundRecorded(
                tenantId: $dto->tenantId,
                purchaseInvoiceId: (int) $saved->getId(),
                supplierId: $saved->getSupplierId(),
                refundPaymentId: (int) $refundPayment->getId(),
                apAccountId: $saved->getApAccountId(),
                cashAccountId: $dto->accountId,
                amount: (string) $dto->amount,
                currencyId: $dto->currencyId,
                exchangeRate: (string) $dto->exchangeRate,
                refundDate: $dto->refundDate,
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
