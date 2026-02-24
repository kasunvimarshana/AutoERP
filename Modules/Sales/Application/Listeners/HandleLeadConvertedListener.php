<?php

namespace Modules\Sales\Application\Listeners;

use Modules\CRM\Domain\Events\LeadConverted;
use Modules\Sales\Application\UseCases\CreateQuotationUseCase;


class HandleLeadConvertedListener
{
    public function __construct(
        private CreateQuotationUseCase $createQuotation,
    ) {}

    public function handle(LeadConverted $event): void
    {
        if ($event->tenantId === '' || $event->contactName === '') {
            return;
        }

        try {
            $this->createQuotation->execute([
                'tenant_id'   => $event->tenantId,
                'customer_id' => null,
                'notes'       => 'Auto-created from CRM lead ' . $event->leadId
                    . ' (opportunity ' . $event->opportunityId . ')'
                    . ($event->contactEmail !== '' ? ' — ' . $event->contactEmail : ''),
                'lines'       => [],
            ]);
        } catch (\Throwable) {
            // Graceful degradation: a quotation creation failure must never
            // prevent the CRM lead conversion from being recorded.
        }
    }
}
