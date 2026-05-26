<?php

declare(strict_types=1);

namespace Modules\VehicleService\Infrastructure\Listeners;

use Illuminate\Support\Facades\DB;
use Modules\VehicleService\Domain\Events\ServiceInvoicePosted;

final class RecordServiceInvoicePosted
{
    public function handle(ServiceInvoicePosted $event): void
    {
        $reference = DB::table('invoice_references')
            ->where('invoice_id', $event->invoiceId)
            ->where('document_type', 'JOB_CARD')
            ->first();

        if ($reference === null) {
            return;
        }

        DB::table('vehicle_service_job_cards')->where('id', (int) $reference->document_id)->update([
            'status' => 'invoiced',
            'updated_at' => now(),
        ]);
    }
}
