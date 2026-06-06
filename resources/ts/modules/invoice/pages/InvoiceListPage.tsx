import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { DateDisplay, EmptyState, FilterCard, LoadingState, MoneyDisplay, PageHeader, Pagination, PrimaryLink, StatusBadge, TableCard } from '../../../shared/components/erp/ErpUi';
import { Input } from '../../../shared/components/ui/Input';
import { invoiceApi } from '../services/invoiceApi';
import type { Invoice, InvoicePage, InvoiceStatus } from '../types/invoice.types';

export function InvoiceListPage() {
    const [pageData, setPageData] = useState<InvoicePage | null>(null);
    const [searchInput, setSearchInput] = useState('');
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState<InvoiceStatus | ''>('');
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
        void invoiceApi.list({ page, perPage: 20, search: search || undefined, status: status || undefined })
            .then((response) => {
                if (active) setPageData(response);
            })
            .catch((requestError) => {
                if (active) setError(requestError instanceof Error ? requestError.message : 'Unable to load invoices.');
            })
            .finally(() => {
                if (active) setLoading(false);
            });
        return () => {
            active = false;
        };
    }, [page, search, status]);

    async function issue(invoice: Invoice) {
        try {
            const updated = await invoiceApi.issue(invoice.id);
            setPageData((current) => current ? { ...current, invoices: current.invoices.map((item) => item.id === updated.id ? updated : item) } : current);
        } catch (requestError) {
            setError(requestError instanceof Error ? requestError.message : 'Unable to issue invoice.');
        }
    }

    return (
        <div className="space-y-5">
            <PageHeader actions={<PrimaryLink to="/invoices/new">Create invoice</PrimaryLink>} eyebrow="Finance" subtitle="Manage billing documents, credit notes, settlement state, and posting status." title="Invoices" />
            <FilterCard className="sm:grid-cols-[1fr_180px]">
                <Input placeholder="Search invoice number" value={searchInput} onChange={(event) => setSearchInput(event.target.value)} />
                <select className="erp-select" value={status} onChange={(event) => { setPage(1); setStatus(event.target.value as InvoiceStatus | ''); }}>
                    <option value="">All statuses</option><option value="draft">Draft</option><option value="issued">Issued</option><option value="partially_paid">Partially paid</option><option value="paid">Paid</option><option value="credited">Credited</option><option value="cancelled">Cancelled</option>
                </select>
            </FilterCard>
            {error ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div> : null}
            <TableCard>
                {loading ? <LoadingState label="Loading invoices" /> : pageData?.invoices.length ? (
                    <div className="overflow-x-auto"><table className="w-full min-w-[920px] text-left text-sm">
                        <thead className="border-b border-slate-200 bg-slate-50/80 text-xs uppercase tracking-wide text-slate-500"><tr><th className="px-4 py-3">Number</th><th className="px-4 py-3">Type</th><th className="px-4 py-3">Date</th><th className="px-4 py-3">Total</th><th className="px-4 py-3">Balance</th><th className="px-4 py-3">Status</th><th className="px-4 py-3 text-right">Actions</th></tr></thead>
                        <tbody className="divide-y divide-slate-100">{pageData.invoices.map((invoice) => <tr className="transition hover:bg-slate-50/70" key={invoice.id}>
                            <td className="px-4 py-4 font-semibold text-slate-900">{invoice.invoiceNumber}</td><td className="px-4 py-4 capitalize text-slate-600">{invoice.documentType.replaceAll('_', ' ')}</td><td className="px-4 py-4 text-slate-600"><DateDisplay value={invoice.invoiceDate} /></td><td className="px-4 py-4 font-semibold"><MoneyDisplay value={invoice.grandTotal} /></td><td className="px-4 py-4 font-semibold"><MoneyDisplay value={invoice.balanceDue} /></td><td className="px-4 py-4"><StatusBadge value={invoice.status} /></td>
                            <td className="px-4 py-4"><div className="flex justify-end gap-2"><Link className="font-semibold text-blue-700" to={`/invoices/${invoice.id}`}>View</Link>{invoice.status === 'draft' ? <button className="font-semibold text-emerald-700" onClick={() => void issue(invoice)} type="button">Issue</button> : null}<Link className="font-semibold text-slate-700" to={`/invoices/${invoice.id}/edit`}>Edit</Link></div></td>
                        </tr>)}</tbody>
                    </table></div>
                ) : <EmptyState action={<PrimaryLink to="/invoices/new">Create invoice</PrimaryLink>} title="No invoices found" />}
            </TableCard>
            {pageData ? <Pagination current={pageData.meta.currentPage} last={pageData.meta.lastPage} loading={loading} onPage={setPage} total={pageData.meta.total} /> : null}
        </div>
    );
}
