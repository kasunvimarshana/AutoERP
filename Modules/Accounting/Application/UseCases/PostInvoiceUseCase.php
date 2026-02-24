<?php

namespace Modules\Accounting\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Accounting\Domain\Contracts\InvoiceRepositoryInterface;
use Modules\Accounting\Domain\Events\InvoicePosted;

class PostInvoiceUseCase
{
    public function __construct(
        private InvoiceRepositoryInterface $repo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $invoice = $this->repo->findById($data['id']);

            if (! $invoice) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Invoice not found.');
            }

            if ($invoice->status !== 'draft') {
                throw new \DomainException('Only draft invoices can be posted.');
            }

            $posted = $this->repo->update($data['id'], ['status' => 'sent']);

            Event::dispatch(new InvoicePosted($posted->id));

            return $posted;
        });
    }
}
