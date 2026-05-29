import type { RouteObject } from 'react-router-dom';
import { lazyNamed } from '../lazyRoutes';

const dashboard = () => lazyNamed(() => import('../../modules/finance/pages/FinanceDashboardPage'), 'FinanceDashboardPage');
const accounts = () => import('../../modules/finance/pages/FinanceAccountPages');
const journals = () => import('../../modules/finance/pages/FinanceJournalPages');
const references = () => import('../../modules/finance/pages/FinanceReferencePages');
const posting = () => lazyNamed(() => import('../../modules/finance/pages/FinancePostingPreviewPage'), 'FinancePostingPreviewPage');

export const financeRoutes: RouteObject[] = [
    { element: dashboard(), path: 'finance' },
    { element: lazyNamed(accounts, 'AccountListPage'), path: 'finance/accounts' },
    { element: lazyNamed(accounts, 'AccountCreatePage'), path: 'finance/accounts/new' },
    { element: lazyNamed(accounts, 'AccountDetailPage'), path: 'finance/accounts/:id' },
    { element: lazyNamed(accounts, 'AccountEditPage'), path: 'finance/accounts/:id/edit' },
    { element: lazyNamed(references, 'FiscalYearListPage'), path: 'finance/fiscal-years' },
    { element: lazyNamed(references, 'FiscalPeriodListPage'), path: 'finance/fiscal-periods' },
    { element: lazyNamed(journals, 'JournalEntryListPage'), path: 'finance/journal-entries' },
    { element: lazyNamed(journals, 'JournalEntryCreatePage'), path: 'finance/journal-entries/new' },
    { element: lazyNamed(journals, 'JournalEntryDetailPage'), path: 'finance/journal-entries/:id' },
    { element: lazyNamed(references, 'ApTransactionListPage'), path: 'finance/ap-transactions' },
    { element: lazyNamed(references, 'ArTransactionListPage'), path: 'finance/ar-transactions' },
    { element: lazyNamed(references, 'TaxDashboardPage'), path: 'finance/tax' },
    { element: lazyNamed(references, 'TaxGroupListPage'), path: 'finance/tax/groups' },
    { element: lazyNamed(references, 'TaxRateListPage'), path: 'finance/tax/rates' },
    { element: lazyNamed(references, 'TaxRuleListPage'), path: 'finance/tax/rules' },
    { element: lazyNamed(references, 'PaymentTermListPage'), path: 'finance/payment-terms' },
    { element: lazyNamed(references, 'CostCenterListPage'), path: 'finance/cost-centers' },
    { element: lazyNamed(references, 'BankAccountListPage'), path: 'finance/bank-accounts' },
    { element: lazyNamed(references, 'BankTransactionListPage'), path: 'finance/bank-transactions' },
    { element: lazyNamed(references, 'BankReconciliationListPage'), path: 'finance/reconciliations' },
    { element: lazyNamed(references, 'BudgetListPage'), path: 'finance/budgets' },
    { element: posting(), path: 'finance/posting-preview' },
];
