<?php

namespace Modules\Accounting\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Accounting\Domain\Contracts\InvoiceRepositoryInterface;
use Modules\Accounting\Domain\Events\PaymentRecorded;

class RecordPaymentUseCase
{
    public function __construct(
        private InvoiceRepositoryInterface $repo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $invoice = $this->repo->findById($data['invoice_id']);

            if (! $invoice) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Invoice not found.');
            }

            if (in_array($invoice->status, ['paid', 'cancelled'], true)) {
                throw new \DomainException('Cannot record payment for a paid or cancelled invoice.');
            }

            $newAmountPaid = bcadd((string) $invoice->amount_paid, (string) $data['amount'], 8);
            $newAmountDue  = bcsub((string) $invoice->total, $newAmountPaid, 8);

            if (bccomp($newAmountDue, '0.00000000', 8) < 0) {
                throw new \DomainException('Payment amount exceeds the amount due.');
            }

            $status = bccomp($newAmountDue, '0.00000000', 8) === 0 ? 'paid' : $invoice->status;

            $updated = $this->repo->update($data['invoice_id'], [
                'amount_paid' => $newAmountPaid,
                'amount_due'  => $newAmountDue,
                'status'      => $status,
            ]);

            Event::dispatch(new PaymentRecorded($updated->id));

            return $updated;
        });
    }
}
