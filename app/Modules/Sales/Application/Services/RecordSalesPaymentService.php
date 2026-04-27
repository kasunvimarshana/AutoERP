<?php

declare(strict_types=1);

namespace Modules\Sales\Application\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\CreatePaymentAllocationServiceInterface;
use Modules\Finance\Application\Contracts\CreatePaymentServiceInterface;
use Modules\Sales\Application\Contracts\RecordSalesPaymentServiceInterface;
use Modules\Sales\Application\DTOs\RecordSalesPaymentData;
use Modules\Sales\Domain\Entities\SalesInvoice;
use Modules\Sales\Domain\Events\SalesPaymentRecorded;
use Modules\Sales\Domain\Exceptions\SalesInvoiceNotFoundException;
use Modules\Sales\Domain\RepositoryInterfaces\SalesInvoiceRepositoryInterface;

class RecordSalesPaymentService extends BaseService implements RecordSalesPaymentServiceInterface
{
    public function __construct(
        private readonly SalesInvoiceRepositoryInterface $invoiceRepository,
        private readonly CreatePaymentServiceInterface $createPaymentService,
        private readonly CreatePaymentAllocationServiceInterface $createPaymentAllocationService,
    ) {
        parent::__construct($invoiceRepository);
    }

    protected function handle(array $data): SalesInvoice
    {
        $dto = RecordSalesPaymentData::fromArray($data);

        $invoice = $this->invoiceRepository->find($dto->invoice_id);

        if (! $invoice) {
            throw new SalesInvoiceNotFoundException($dto->invoice_id);
        }

        if (! in_array($invoice->getStatus(), ['sent', 'partial_paid', 'overdue'], true)) {
            throw new \InvalidArgumentException('Payment can only be recorded against sent, partially paid, or overdue invoices.');
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

        return DB::transaction(function () use ($dto, $invoice): SalesInvoice {
            $payment = $this->createPaymentService->execute([
                'tenant_id' => $dto->tenant_id,
                'payment_number' => $dto->payment_number,
                'direction' => 'inbound',
                'party_type' => 'customer',
                'party_id' => $invoice->getCustomerId(),
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
                'invoice_type' => 'sales_invoice',
                'invoice_id' => $invoice->getId(),
                'allocated_amount' => (float) $dto->amount,
                'tenant_id' => $dto->tenant_id,
            ]);

            $invoice->recordPayment((string) $dto->amount);

            $saved = $this->invoiceRepository->save($invoice);

            $this->addEvent(new SalesPaymentRecorded(
                tenantId: $dto->tenant_id,
                salesInvoiceId: (int) $saved->getId(),
                customerId: $saved->getCustomerId(),
                paymentId: (int) $payment->getId(),
                arAccountId: $saved->getArAccountId(),
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
