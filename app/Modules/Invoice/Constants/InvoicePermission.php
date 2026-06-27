<?php

declare(strict_types=1);

namespace Modules\Invoice\Constants;

final class InvoicePermission
{
    public const VIEW = 'invoices.view';
    public const CREATE = 'invoices.create';
    public const APPROVE = 'invoices.approve';
    public const POST = 'invoices.post';
    public const CANCEL = 'invoices.cancel';

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return [
            self::VIEW => 'View invoices, balances, sources, and adjustments.',
            self::CREATE => 'Preview and create invoices from approved business facts.',
            self::APPROVE => 'Approve draft invoices.',
            self::POST => 'Post approved invoices.',
            self::CANCEL => 'Cancel invoices through the governed correction workflow.',
        ];
    }
}
