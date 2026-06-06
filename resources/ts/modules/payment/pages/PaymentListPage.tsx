import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { DateDisplay, EmptyState, FilterCard, LoadingState, MoneyDisplay, PageHeader, Pagination, PrimaryLink, StatusBadge, TableCard } from '../../../shared/components/erp/ErpUi';
import { Input } from '../../../shared/components/ui/Input';
import { paymentApi } from '../services/paymentApi';
import type { PaymentDirection, PaymentPage } from '../types/payment.types';

export function PaymentListPage() {
    const [pageData, setPageData] = useState<PaymentPage | null>(null);
    const [searchInput, setSearchInput] = useState('');
    const [search, setSearch] = useState('');
    const [direction, setDirection] = useState<PaymentDirection | ''>('');
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
        void paymentApi.list({ direction: direction || undefined, page, perPage: 20, search: search || undefined })
            .then((response) => {
                if (active) setPageData(response);
            })
            .catch((requestError) => {
                if (active) setError(requestError instanceof Error ? requestError.message : 'Unable to load payments.');
            })
            .finally(() => {
                if (active) setLoading(false);
            });
        return () => {
            active = false;
        };
    }, [direction, page, search]);

    return (
        <div className="space-y-5">
            <PageHeader actions={<PrimaryLink to="/payments/new">Create payment</PrimaryLink>} eyebrow="Finance" subtitle="Track inbound and outbound settlements, allocations, and unallocated balances." title="Payments" />
            <FilterCard className="sm:grid-cols-[1fr_180px]">
                <Input placeholder="Search payment number" value={searchInput} onChange={(event) => setSearchInput(event.target.value)} />
                <select className="erp-select" value={direction} onChange={(event) => { setPage(1); setDirection(event.target.value as PaymentDirection | ''); }}>
                    <option value="">All directions</option><option value="inbound">Inbound</option><option value="outbound">Outbound</option>
                </select>
            </FilterCard>
            {error ? <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div> : null}
            <TableCard>
                {loading ? <LoadingState label="Loading payments" /> : pageData?.payments.length ? (
                    <div className="overflow-x-auto"><table className="w-full min-w-[820px] text-left text-sm">
                        <thead className="border-b border-slate-200 bg-slate-50/80 text-xs uppercase tracking-wide text-slate-500"><tr><th className="px-4 py-3">Number</th><th className="px-4 py-3">Date</th><th className="px-4 py-3">Direction</th><th className="px-4 py-3">Amount</th><th className="px-4 py-3">Allocated</th><th className="px-4 py-3">Status</th><th className="px-4 py-3 text-right">Action</th></tr></thead>
                        <tbody className="divide-y divide-slate-100">{pageData.payments.map((payment) => <tr className="transition hover:bg-slate-50/70" key={payment.id}><td className="px-4 py-4 font-semibold text-slate-900">{payment.paymentNumber}</td><td className="px-4 py-4"><DateDisplay value={payment.paymentDate} /></td><td className="px-4 py-4 capitalize">{payment.direction}</td><td className="px-4 py-4 font-semibold"><MoneyDisplay value={payment.amount} /></td><td className="px-4 py-4"><MoneyDisplay value={payment.allocatedAmount} /></td><td className="px-4 py-4"><StatusBadge value={payment.status} /></td><td className="px-4 py-4 text-right"><Link className="font-semibold text-blue-700" to={`/payments/${payment.id}`}>View</Link></td></tr>)}</tbody>
                    </table></div>
                ) : <EmptyState action={<PrimaryLink to="/payments/new">Create payment</PrimaryLink>} title="No payments found" />}
            </TableCard>
            {pageData ? <Pagination current={pageData.meta.currentPage} last={pageData.meta.lastPage} loading={loading} onPage={setPage} total={pageData.meta.total} /> : null}
        </div>
    );
}
