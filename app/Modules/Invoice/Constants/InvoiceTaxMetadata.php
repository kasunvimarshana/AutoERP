<?php

declare(strict_types=1);

namespace Modules\Invoice\Constants;

final class InvoiceTaxMetadata
{
    public const TAX_GROUP_ID = 'tax_group_id';

    public const TAXES = 'taxes';

    public const WITHHOLDING_AMOUNT = 'withholding_amount';

    public const CALCULATION_METHOD = 'calculation_method';

    public const TAX_AMOUNT = 'tax_amount';

    public const IS_WITHHOLDING = 'is_withholding';

    public const CALCULATION_METHOD_INCLUSIVE = 'inclusive';
}
