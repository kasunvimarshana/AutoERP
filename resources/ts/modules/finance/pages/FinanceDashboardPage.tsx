import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { Button } from '../../../shared/components/ui/Button';
import { FinanceDashboardCards, JournalEntryLineTable, BankTransactionTable, TaxPreviewPanel } from '../components/FinanceComponents';
import { bankTransactions, financeDashboardMetrics, journalEntries, taxPreview } from '../mock/financeMock';

export function FinanceDashboardPage() {
    return (
        <div className="space-y-6">
            <PageHeader
                actions={<Link to="/finance/posting-preview"><Button>Posting Preview</Button></Link>}
                eyebrow="Core Finance"
                subtitle="Generic accounting workspace for accounts, journals, AP/AR, tax, bank, budgets, and posting previews. Backend owns all accounting calculations."
                title="Finance"
            />
            <FinanceDashboardCards metrics={financeDashboardMetrics} />
            <div className="grid gap-4 md:grid-cols-5">
                {[
                    ['Create journal', '/finance/journal-entries/new'],
                    ['Chart of accounts', '/finance/accounts'],
                    ['Tax setup', '/finance/tax'],
                    ['Bank accounts', '/finance/bank-accounts'],
                    ['Budgets', '/finance/budgets'],
                ].map(([label, path]) => <Link className="rounded-lg border border-slate-200 bg-white p-5 text-sm font-bold text-slate-900 shadow-sm hover:border-slate-300" key={label} to={path}>{label}</Link>)}
            </div>
            <section className="space-y-3">
                <h2 className="text-sm font-bold uppercase tracking-wide text-slate-500">Latest Draft Journal Lines</h2>
                <JournalEntryLineTable rows={journalEntries[0]?.lines ?? []} />
            </section>
            <section className="grid gap-4 xl:grid-cols-2">
                <div className="space-y-3">
                    <h2 className="text-sm font-bold uppercase tracking-wide text-slate-500">Unreconciled Bank Transactions</h2>
                    <BankTransactionTable rows={bankTransactions} />
                </div>
                <TaxPreviewPanel preview={taxPreview} />
            </section>
        </div>
    );
}

export { FinanceDashboardPage as FinancePage };
