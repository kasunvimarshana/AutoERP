import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { DateDisplay, EmptyState, FilterCard, LoadingState, MoneyDisplay, PageHeader, Pagination, StatusBadge, TableCard } from '../../../shared/components/erp/ErpUi';
import { Input } from '../../../shared/components/ui/Input';
import { journalApi } from '../services/journalApi';
import type { JournalPage } from '../types/journal.types';

export function JournalListPage() {
    const [pageData, setPageData] = useState<JournalPage | null>(null);
    const [searchInput, setSearchInput] = useState('');
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');

    useEffect(() => {
        const timer = window.setTimeout(() => {
            setPage(1);
            setSearch(searchInput.trim());
        }, 350);
        return () => window.clearTimeout(timer);
    }, [searchInput]);

    useEffect(() => {
        let active = true;
        setLoading(true);
        setError('');
        void journalApi.list({ page, perPage: 20, search: search || undefined })
            .then((response) => {
                if (active) setPageData(response);
            })
            .catch((requestError) => {
                if (active) setError(requestError instanceof Error ? requestError.message : 'Unable to load journals.');
            })
            .finally(() => {
                if (active) setLoading(false);
            });
        return () => {
            active = false;
        };
    }, [page, search]);

    return (
        <div className="space-y-5">
            <PageHeader eyebrow="Finance" subtitle="Review double-entry postings created by invoice and payment workflows." title="Journal entries" />
            <FilterCard><Input placeholder="Search journal number or source reference" value={searchInput} onChange={(event) => setSearchInput(event.target.value)} /></FilterCard>
            {error ? <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div> : null}
            <TableCard>
                {loading ? <LoadingState label="Loading journal entries" /> : pageData?.entries.length ? (
                    <div className="overflow-x-auto"><table className="w-full min-w-[820px] text-left text-sm">
                        <thead className="border-b border-slate-200 bg-slate-50/80 text-xs uppercase tracking-wide text-slate-500"><tr><th className="px-4 py-3">Number</th><th className="px-4 py-3">Date</th><th className="px-4 py-3">Source</th><th className="px-4 py-3">Debit</th><th className="px-4 py-3">Credit</th><th className="px-4 py-3">Status</th><th className="px-4 py-3 text-right">Action</th></tr></thead>
                        <tbody className="divide-y divide-slate-100">{pageData.entries.map((entry) => <tr className="transition hover:bg-slate-50/70" key={entry.id}><td className="px-4 py-4 font-semibold text-slate-900">{entry.entryNumber}</td><td className="px-4 py-4"><DateDisplay value={entry.entryDate} /></td><td className="px-4 py-4">{entry.sourceReference || entry.sourceModule || 'Manual'}</td><td className="px-4 py-4 font-semibold"><MoneyDisplay value={entry.totalDebit} /></td><td className="px-4 py-4 font-semibold"><MoneyDisplay value={entry.totalCredit} /></td><td className="px-4 py-4"><StatusBadge value={entry.status} /></td><td className="px-4 py-4 text-right"><Link className="font-semibold text-blue-700" to={`/finance/journals/${entry.id}`}>View</Link></td></tr>)}</tbody>
                    </table></div>
                ) : <EmptyState description="Journal entries will appear after invoices and payments are posted." title="No journal entries found" />}
            </TableCard>
            {pageData ? <Pagination current={pageData.meta.currentPage} last={pageData.meta.lastPage} loading={loading} onPage={setPage} total={pageData.meta.total} /> : null}
        </div>
    );
}
