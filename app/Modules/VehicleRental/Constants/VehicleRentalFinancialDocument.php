<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Constants;

final class VehicleRentalFinancialDocument
{
    public const ZERO = '0.000000';

    public const DEFAULT_EXCHANGE_RATE = '1.000000';

    public const CUSTOMER_TAX_DOCUMENT = 'vehicle_rental_customer_invoice';

    public const OWNER_TAX_DOCUMENT = 'vehicle_rental_owner_payable';

    public const WITHHOLDING_ADJUSTMENT_NAME = 'Tax withholding';

    public const WITHHOLDING_ADJUSTMENT_DESCRIPTION = 'Withholding calculated by the Tax module.';

    public const FIXED_CALCULATION_TYPE = 'fixed';

    public const CUSTOMER_INVOICE_DESCRIPTION = 'Vehicle rental customer invoice';

    public const OWNER_PAYABLE_DESCRIPTION = 'Vehicle rental owner payable voucher';

    private function __construct() {}
}
