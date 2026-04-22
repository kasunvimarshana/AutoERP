<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\CreatePaymentAllocationServiceInterface;
use Modules\Finance\Application\Contracts\CreatePaymentServiceInterface;
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
    ) {
        parent::__construct($invoiceRepository);
    }

    protected function handle(array $data): PurchaseInvoice
    {
        $dto = RecordPurchasePaymentData::fromArray($data);

        $invoice = $this->invoiceRepository->find($dto->invoice_id);

        if (! $invoice) {
            throw new PurchaseInvoiceNotFoundException($dto->invoice_id);
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
                'tenant_id' => $dto->tenant_id,
                'payment_number' => $dto->payment_number,
                'direction' => 'outgoing',
                'party_type' => 'supplier',
                'party_id' => $invoice->getSupplierId(),
                'payment_method_id' => $dto->payment_method_id,
                'account_id' => $dto->account_id,
                'amount' => (float) $dto->amount,
                'currency_id' => $dto->currency_id,
                'payment_date' => $dto->payment_date,
                'exchange_rate' => $dto->exchange_rate,
                'reference' => $dto->reference,
                'notes' => $dto->notes,
                'status' => 'posted',
            ]);

            $this->createPaymentAllocationService->execute([
                'payment_id' => $payment->getId(),
                'invoice_type' => 'purchase_invoice',
                'invoice_id' => $invoice->getId(),
                'allocated_amount' => (float) $dto->amount,
                'tenant_id' => $dto->tenant_id,
            ]);

            $invoice->recordPayment((string) $dto->amount);

            $saved = $this->invoiceRepository->save($invoice);

            $this->addEvent(new PurchasePaymentRecorded(
                tenantId: $dto->tenant_id,
                purchaseInvoiceId: (int) $saved->getId(),
                supplierId: $saved->getSupplierId(),
                paymentId: (int) $payment->getId(),
                apAccountId: $saved->getApAccountId(),
                cashAccountId: $dto->account_id,
                amount: (string) $dto->amount,
                currencyId: $dto->currency_id,
                exchangeRate: (string) $dto->exchange_rate,
                paymentDate: $dto->payment_date,
                createdBy: (int) (Auth::id() ?? 0),
            ));

            return $saved;
        });
    }
}
