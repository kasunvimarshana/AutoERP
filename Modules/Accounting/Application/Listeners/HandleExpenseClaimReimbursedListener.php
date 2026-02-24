<?php

namespace Modules\Accounting\Application\Listeners;

use Modules\Accounting\Application\UseCases\CreateInvoiceUseCase;
use Modules\Expense\Domain\Events\ExpenseClaimReimbursed;

class HandleExpenseClaimReimbursedListener
{
    public function __construct(
        private CreateInvoiceUseCase $createInvoice,
    ) {}

    public function handle(ExpenseClaimReimbursed $event): void
    {
        if ($event->tenantId === '') {
            return;
        }

        if (bccomp($event->totalAmount, '0', 8) <= 0) {
            return;
        }

        try {
            $this->createInvoice->execute([
                'tenant_id'    => $event->tenantId,
                'invoice_type' => 'vendor_bill',
                'partner_id'   => $event->employeeId !== '' ? $event->employeeId : null,
                'partner_type' => 'vendor',
                'currency'     => $event->currency !== '' ? $event->currency : 'USD',
                'notes'        => 'Auto-created from expense claim reimbursement ' . $event->claimId,
                'lines'        => [
                    [
                        'product_id'  => null,
                        'description' => 'Expense reimbursement — claim ' . $event->claimId,
                        'quantity'    => '1',
                        'unit_price'  => $event->totalAmount,
                        'tax_rate'    => '0',
                    ],
                ],
            ]);
        } catch (\Throwable) {
            // Graceful degradation: a vendor bill creation failure must never
            // prevent the expense claim from being reimbursed.
        }
    }
}
