import { ModulePlaceholderPage } from '../../../shared/components/business/ModulePlaceholderPage';

export function FinancePage() {
    return (
        <ModulePlaceholderPage
            description="Chart of accounts, journals, taxes, AP/AR, bank accounts, reconciliations, budgets, and posting previews. Backend owns double-entry validation, period locks, ledgers, and balances."
            sections={[
                { description: 'Chart of accounts and account mappings.', label: 'Chart of Accounts', path: '/finance/accounts', status: 'Ready' },
                { description: 'Journal entry draft, preview, post, and reverse workflow.', label: 'Journal Entries', path: '/finance/journal-entries', status: 'Mocked' },
                { description: 'Tax groups, rates, rules, and backend tax preview.', label: 'Tax', path: '/finance/tax', status: 'Mocked' },
                { description: 'Bank accounts, transactions, and reconciliation consoles.', label: 'Banks', path: '/finance/banks', status: 'Mocked' },
            ]}
            title="Finance"
        />
    );
}
