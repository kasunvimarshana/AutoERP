<?php

declare(strict_types=1);

namespace Modules\Finance\Constants;

final class FinancePermission
{
    public const VIEW = 'finance.view';
    public const MANAGE = 'finance.manage';
    public const POST = 'finance.post';
    public const REVERSE = 'finance.reverse';

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return [
            self::VIEW => 'View finance accounts, journals, ledgers, and financial reports.',
            self::MANAGE => 'Manage finance master data, draft journals, periods, reconciliations, and budgets.',
            self::POST => 'Post governed finance transactions and operational accounting events.',
            self::REVERSE => 'Reverse posted journals through the governed correction workflow.',
        ];
    }
}
