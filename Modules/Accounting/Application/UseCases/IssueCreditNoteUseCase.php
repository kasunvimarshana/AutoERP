<?php

namespace Modules\Accounting\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Accounting\Domain\Contracts\InvoiceRepositoryInterface;
use Modules\Accounting\Domain\Events\CreditNoteIssued;

class IssueCreditNoteUseCase
{
    public function __construct(
        private InvoiceRepositoryInterface $repo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $source = $this->repo->findById($data['source_invoice_id']);

            if (! $source) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Source invoice not found.');
            }

            if (! in_array($source->status, ['sent', 'overdue'], true)) {
                throw new \DomainException('Credit notes can only be issued against posted (sent/overdue) invoices.');
            }

            $amount = bcadd((string) $data['amount'], '0.00000000', 8);

            if (bccomp($amount, '0.00000000', 8) <= 0) {
                throw new \DomainException('Credit note amount must be greater than zero.');
            }

            if (bccomp($amount, (string) $source->total, 8) > 0) {
                throw new \DomainException('Credit note amount cannot exceed the source invoice total.');
            }

            $number = $this->repo->nextCreditNoteNumber($data['tenant_id'] ?? $source->tenant_id);

            $creditNote = $this->repo->create([
                'tenant_id'         => $data['tenant_id'] ?? $source->tenant_id,
                'number'            => $number,
                'invoice_type'      => 'credit_note',
                'source_invoice_id' => $source->id,
                'partner_id'        => $source->partner_id,
                'partner_type'      => $source->partner_type,
                'status'            => 'draft',
                'subtotal'          => $amount,
                'tax_total'         => '0.00000000',
                'total'             => $amount,
                'amount_paid'       => '0.00000000',
                'amount_due'        => $amount,
                'currency'          => $source->currency,
                'notes'             => $data['notes'] ?? null,
                'created_by'        => $data['created_by'] ?? null,
            ]);

            Event::dispatch(new CreditNoteIssued(
                $creditNote->id,
                $creditNote->tenant_id,
                $source->id,
                $amount,
            ));

            return $creditNote;
        });
    }
}
