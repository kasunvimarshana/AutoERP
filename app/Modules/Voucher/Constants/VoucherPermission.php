<?php

declare(strict_types=1);

namespace Modules\Voucher\Constants;

final class VoucherPermission
{
    public const VIEW = 'vouchers.view';
    public const PRINT = 'vouchers.print';

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return [
            self::VIEW => 'View governed voucher projections.',
            self::PRINT => 'Render printable voucher documents.',
        ];
    }
}
