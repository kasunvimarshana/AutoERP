<?php

declare(strict_types=1);

namespace Modules\Invoice\Constants;

final class RetiredInvoiceSource
{
    /** @var list<string> */
    public const TYPES = [
        'rental_calculation_run',
        'rental_calculation_line',
        'vehicle_finance_installment',
        'vehicle_finance_installment_component',
    ];

    private function __construct() {}
}
