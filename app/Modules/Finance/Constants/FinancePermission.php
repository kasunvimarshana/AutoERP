<?php

declare(strict_types=1);

namespace Modules\Finance\Constants;

final class FinancePermission
{
    public const ACCOUNTS_VIEW = 'finance.accounts.view';

    public const ACCOUNTS_MANAGE = 'finance.accounts.manage';

    public const POSTING_PROFILES_VIEW = 'finance.posting_profiles.view';

    public const POSTING_PROFILES_MANAGE = 'finance.posting_profiles.manage';

    public const JOURNALS_VIEW = 'finance.journals.view';

    public const JOURNALS_CREATE = 'finance.journals.create';

    public const JOURNALS_UPDATE = 'finance.journals.update';

    public const JOURNALS_CANCEL = 'finance.journals.cancel';

    public const JOURNALS_POST = 'finance.journals.post';

    public const JOURNALS_REVERSE = 'finance.journals.reverse';

    public const REPORTS_VIEW = 'finance.reports.view';

    public const CURRENCY_REVALUATIONS_POST = 'finance.currency_revaluations.post';

    public const FISCAL_CALENDAR_VIEW = 'finance.fiscal_calendar.view';

    public const FISCAL_CALENDAR_MANAGE = 'finance.fiscal_calendar.manage';

    public const BANK_RECONCILIATIONS_VIEW = 'finance.bank_reconciliations.view';

    public const BANK_RECONCILIATIONS_MANAGE = 'finance.bank_reconciliations.manage';

    public const BUDGETS_VIEW = 'finance.budgets.view';

    public const BUDGETS_MANAGE = 'finance.budgets.manage';

    /**
     * @return array<string, string>
     */
    public static function descriptions(): array
    {
        return [
            self::ACCOUNTS_VIEW => 'View the chart of accounts, account details, lookups, and account balances.',
            self::ACCOUNTS_MANAGE => 'Create and update chart-of-accounts records.',
            self::POSTING_PROFILES_VIEW => 'View Finance posting-profile configuration.',
            self::POSTING_PROFILES_MANAGE => 'Create and update Finance posting profiles.',
            self::JOURNALS_VIEW => 'View Finance journals and journal details.',
            self::JOURNALS_CREATE => 'Create draft Finance journals.',
            self::JOURNALS_UPDATE => 'Update editable draft Finance journals.',
            self::JOURNALS_CANCEL => 'Cancel eligible draft Finance journals.',
            self::JOURNALS_POST => 'Post eligible Finance journals to the ledger.',
            self::JOURNALS_REVERSE => 'Reverse posted Finance journals through governed reversal entries.',
            self::REPORTS_VIEW => 'View ledgers, balances, statements, aging, and Finance tax reports.',
            self::CURRENCY_REVALUATIONS_POST => 'Post governed foreign-currency revaluation journals.',
            self::FISCAL_CALENDAR_VIEW => 'View fiscal years and fiscal periods.',
            self::FISCAL_CALENDAR_MANAGE => 'Change governed fiscal-year and fiscal-period states.',
            self::BANK_RECONCILIATIONS_VIEW => 'View bank reconciliations and statement lines.',
            self::BANK_RECONCILIATIONS_MANAGE => 'Create, complete, match, and unmatch bank reconciliations.',
            self::BUDGETS_VIEW => 'View Finance budgets, budget details, and actuals.',
            self::BUDGETS_MANAGE => 'Create and update Finance budgets.',
        ];
    }
}
