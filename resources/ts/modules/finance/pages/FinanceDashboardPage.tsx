import { useState } from 'react';
import { Link } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { ApiErrorBanner, BankTransactionTable, FinanceDashboardCards, JournalEntryLineTable, TaxPreviewPanel } from '../components/FinanceComponents';
import { financeApi } from '../services/financeApi';
import type { BankTransaction, FinanceDashboardMetric, JournalEntry, TaxPreviewResult } from '../types/finance.types';

export function FinanceDashboardPage() {
    const [metrics, setMetrics] = useState<FinanceDashboardMetric[]>([]);
    const [journals, setJournals] = useState<JournalEntry[]>([]);
    const [bankTransactions, setBankTransactions] = useState<BankTransaction[]>([]);
    const [taxPreview] = useState<TaxPreviewResult>();
    const [error, setError] = useState<Error | null>(null);
    const [isLoaded, setIsLoaded] = useState(false);
    const [isLoading, setIsLoading] = useState(false);

    async function loadDashboardData(): Promise<void> {
        if (isLoading) return;

        setIsLoading(true);
        setError(null);

        try {
            const [metricResponse, journalResponse, bankResponse] = await Promise.all([
            financeApi.listDashboardMetrics(),
            financeApi.listJournalEntries({ per_page: 3 }),
            financeApi.listBankTransactions({ per_page: 5 }),
            ]);

            setMetrics(metricResponse.data);
            setJournals(journalResponse.data);
            setBankTransactions(bankResponse.data);
            setIsLoaded(true);
        } catch (caught) {
            setError(caught instanceof Error ? caught : new Error('Unable to load Finance dashboard.'));
        } finally {
            setIsLoading(false);
        }
    }

    return (
        <div className="space-y-6">
            <PageHeader
                actions={<Link to="/finance/posting-preview"><Button>Posting Preview</Button></Link>}
                eyebrow="Core Finance"
                subtitle="Generic accounting workspace for accounts, journals, AP/AR, tax, bank, budgets, and posting previews. Backend owns all accounting calculations."
                title="Finance"
            />
            <ApiErrorBanner error={error} />
            <div className="flex justify-end">
                <Button disabled={isLoading} onClick={() => void loadDashboardData()} type="button" variant="secondary">{isLoaded ? 'Refresh Dashboard Data' : 'Load Dashboard Data'}</Button>
            </div>
            {!isLoaded && !error ? <EmptyState description="Finance metrics and recent transactions load only when requested." title="Dashboard data not loaded" /> : null}
            {isLoaded ? <FinanceDashboardCards metrics={metrics} /> : null}
            <div className="grid gap-4 md:grid-cols-5">
                {[
                    ['Create journal', '/finance/journal-entries/new'],
                    ['Chart of accounts', '/finance/accounts'],
                    ['Tax setup', '/finance/tax'],
                    ['Bank accounts', '/finance/bank-accounts'],
                    ['Budgets', '/finance/budgets'],
                ].map(([label, path]) => <Link className="rounded-lg border border-slate-200 bg-white p-5 text-sm font-bold text-slate-900 shadow-sm hover:border-slate-300" key={label} to={path}>{label}</Link>)}
            </div>
            {isLoaded ? <section className="space-y-3">
                <h2 className="text-sm font-bold uppercase tracking-wide text-slate-500">Latest Journal Lines</h2>
                {journals[0]?.lines?.length ? <JournalEntryLineTable rows={journals[0].lines} /> : <EmptyState description="No journal lines were returned by the backend." title="No journal lines" />}
            </section> : null}
            {isLoaded ? <section className="grid gap-4 xl:grid-cols-2">
                <div className="space-y-3">
                    <h2 className="text-sm font-bold uppercase tracking-wide text-slate-500">Bank Transactions</h2>
                    <BankTransactionTable rows={bankTransactions} />
                </div>
                <TaxPreviewPanel preview={taxPreview} />
            </section> : null}
        </div>
    );
}

export { FinanceDashboardPage as FinancePage };
