<?php

namespace Modules\Accounting\Application\Listeners;

use Modules\Accounting\Application\UseCases\CreateInvoiceUseCase;
use Modules\SubscriptionBilling\Domain\Events\SubscriptionRenewed;


class HandleSubscriptionRenewedListener
{
    public function __construct(
        private CreateInvoiceUseCase $createInvoice,
    ) {}

    public function handle(SubscriptionRenewed $event): void
    {
        if ($event->tenantId === '') {
            return;
        }

        if (bccomp($event->amount, '0', 8) <= 0) {
            return;
        }

        $description = 'Subscription renewal'
            . ($event->planName !== '' ? ' — ' . $event->planName : '')
            . ($event->currentPeriodStart !== '' && $event->currentPeriodEnd !== ''
                ? ' (' . $event->currentPeriodStart . ' – ' . $event->currentPeriodEnd . ')'
                : '');

        try {
            $this->createInvoice->execute([
                'tenant_id'    => $event->tenantId,
                'invoice_type' => 'invoice',
                'partner_id'   => $event->subscriberId !== '' ? $event->subscriberId : null,
                'partner_type' => 'customer',
                'currency'     => $event->currency !== '' ? $event->currency : 'USD',
                'notes'        => 'Auto-created from subscription renewal ' . $event->subscriptionId,
                'lines'        => [
                    [
                        'product_id'  => null,
                        'description' => $description,
                        'quantity'    => '1',
                        'unit_price'  => $event->amount,
                        'tax_rate'    => '0',
                    ],
                ],
            ]);
        } catch (\Throwable) {
            // Graceful degradation: an invoice creation failure must never
            // prevent the subscription from being renewed.
        }
    }
}
