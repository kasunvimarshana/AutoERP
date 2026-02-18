<?php

namespace App\Modules\Finance\Listeners;

use App\Modules\Finance\Events\FiscalYearClosed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotifyOnFiscalYearClosed implements ShouldQueue
{
    public function handle(FiscalYearClosed $event): void
    {
        $fiscalYear = $event->fiscalYear;

        Log::info('Fiscal year closed', [
            'fiscal_year_id' => $fiscalYear->id,
            'name' => $fiscalYear->name,
        ]);

        // Placeholder for notification implementation
        // This would notify relevant users about fiscal year closure
    }
}
