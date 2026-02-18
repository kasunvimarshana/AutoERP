<?php

namespace App\Modules\Finance\Events;

use App\Modules\Finance\Models\FiscalYear;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FiscalYearClosed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public FiscalYear $fiscalYear
    ) {}
}
