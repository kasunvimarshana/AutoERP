<?php

declare(strict_types=1);

namespace Modules\Invoice\Constants;

final class InvoicePermission
{
    public const VIEW = 'invoices.view';

    public const PREVIEW = 'invoices.preview';

    public const CREATE = 'invoices.create';

    public const APPROVE = 'invoices.approve';

    public const POST = 'invoices.post';

    public const REVERSE = 'invoices.reverse';

    public const CANCEL = 'invoices.cancel';

    public const VIEW_BALANCE = 'invoices.balance.view';

    public const VIEW_SOURCES = 'invoices.sources.view';

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return [
            self::VIEW => 'View invoice registers and invoice details.',
            self::PREVIEW => 'Preview server-calculated manual invoices before creation.',
            self::CREATE => 'Create manual invoice drafts.',
            self::APPROVE => 'Approve eligible invoice drafts.',
            self::POST => 'Post approved invoices and their governed financial effects.',
            self::REVERSE => 'Reverse eligible posted invoices and their governed financial effects.',
            self::CANCEL => 'Cancel eligible unposted invoices.',
            self::VIEW_BALANCE => 'View invoice settlement balances.',
            self::VIEW_SOURCES => 'View invoice source documents, source lines, and adjustments.',
        ];
    }
}
