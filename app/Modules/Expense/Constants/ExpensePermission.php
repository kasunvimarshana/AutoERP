<?php

declare(strict_types=1);

namespace Modules\Expense\Constants;

final class ExpensePermission
{
    public const EXPENSES_VIEW = 'expenses.view';

    public const EXPENSES_CREATE = 'expenses.create';

    public const EXPENSES_REVERSE = 'expenses.reverse';

    public const TYPES_VIEW = 'expense-types.view';

    public const TYPES_MANAGE = 'expense-types.manage';

    public static function descriptions(): array
    {
        return [
            self::EXPENSES_VIEW => 'View branch expense registers and expense details.',
            self::EXPENSES_CREATE => 'Record and post branch expenses to Finance.',
            self::EXPENSES_REVERSE => 'Reverse posted expenses and their Finance journals.',
            self::TYPES_VIEW => 'View expense type setup.',
            self::TYPES_MANAGE => 'Create, update, activate, or deactivate expense types.',
        ];
    }
}
