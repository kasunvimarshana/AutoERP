import { useEffect, useState } from 'react';
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

    useEffect(() => {
        let active = true;

        Promise.all([
            financeApi.listDashboardMetrics(),
            financeApi.listJournalEntries({ per_page: 3 }),
            financeApi.listBankTransactions({ per_page: 5 }),
        ])
            .then(([metricResponse, journalResponse, bankResponse]) => {
                if (!active) {
                    return;
                }

                setMetrics(metricResponse.data);
                setJournals(journalResponse.data);
                setBankTransactions(bankResponse.data);
            })
            .catch((caught: Error) => {
                if (active) {
                    setError(caught);
                }
            });

        return () => {
            active = false;
        };
    }, []);

    return (
        <div className="space-y-6">
            <PageHeader
                actions={<Link to="/finance/posting-preview"><Button>Posting Preview</Button></Link>}
                eyebrow="Core Finance"
                subtitle="Generic accounting workspace for accounts, journals, AP/AR, tax, bank, budgets, and posting previews. Backend owns all accounting calculations."
                title="Finance"
            />
            <ApiErrorBanner error={error} />
            <FinanceDashboardCards metrics={metrics} />
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
                <h2 className="text-sm font-bold uppercase tracking-wide text-slate-500">Latest Journal Lines</h2>
                {journals[0]?.lines?.length ? <JournalEntryLineTable rows={journals[0].lines} /> : <EmptyState description="No journal lines were returned by the backend." title="No journal lines" />}
            </section>
            <section className="grid gap-4 xl:grid-cols-2">
                <div className="space-y-3">
                    <h2 className="text-sm font-bold uppercase tracking-wide text-slate-500">Bank Transactions</h2>
                    <BankTransactionTable rows={bankTransactions} />
                </div>
                <TaxPreviewPanel preview={taxPreview} />
            </section>
        </div>
    );
}

export { FinanceDashboardPage as FinancePage };
