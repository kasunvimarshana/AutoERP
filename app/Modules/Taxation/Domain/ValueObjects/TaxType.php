<?php

declare(strict_types=1);

namespace Modules\Taxation\Domain\ValueObjects;

class TaxType
{
    public const VAT = 'vat';
    public const GST = 'gst';
    public const SALES_TAX = 'sales_tax';
    public const EXCISE = 'excise';
    public const CUSTOMS = 'customs';
    public const WITHHOLDING = 'withholding';
    public const SERVICE_TAX = 'service_tax';
    public const INCOME_TAX = 'income_tax';

    public static function values(): array
    {
        return [
            self::VAT,
            self::GST,
            self::SALES_TAX,
            self::EXCISE,
            self::CUSTOMS,
            self::WITHHOLDING,
            self::SERVICE_TAX,
            self::INCOME_TAX,
        ];
    }
}
