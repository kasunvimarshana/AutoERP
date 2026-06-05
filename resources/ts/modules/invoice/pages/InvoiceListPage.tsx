import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import { Spinner } from '../../../shared/components/ui/Spinner';
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

    useEffect(() => { const timer = window.setTimeout(() => { setPage(1); setSearch(searchInput.trim()); }, 350); return () => window.clearTimeout(timer); }, [searchInput]);
    useEffect(() => {
        let active = true; setLoading(true); setError('');
        void invoiceApi.list({ page, perPage: 20, search: search || undefined, status: status || undefined }).then((response) => { if (active) setPageData(response); }).catch((requestError) => { if (active) setError(requestError instanceof Error ? requestError.message : 'Unable to load invoices.'); }).finally(() => { if (active) setLoading(false); });
        return () => { active = false; };
    }, [page, search, status]);

    async function issue(invoice: Invoice) {
        try { const updated = await invoiceApi.issue(invoice.id); setPageData((current) => current ? { ...current, invoices: current.invoices.map((item) => item.id === updated.id ? updated : item) } : current); } catch (requestError) { setError(requestError instanceof Error ? requestError.message : 'Unable to issue invoice.'); }
    }

    return <div className="space-y-5"><header className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><p className="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Billing</p><h1 className="mt-1 text-3xl font-bold text-slate-950">Invoices</h1><p className="mt-1 text-sm text-slate-500">Billing documents, credit notes, settlement state, and finance posting.</p></div><Link className="inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700" to="/invoices/new">Create invoice</Link></header><div className="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_180px]"><Input placeholder="Search invoice number" value={searchInput} onChange={(event) => setSearchInput(event.target.value)} /><select className="h-11 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm" value={status} onChange={(event) => { setPage(1); setStatus(event.target.value as InvoiceStatus | ''); }}><option value="">All statuses</option><option value="draft">Draft</option><option value="issued">Issued</option><option value="partially_paid">Partially paid</option><option value="paid">Paid</option><option value="credited">Credited</option><option value="cancelled">Cancelled</option></select></div>{error ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div> : null}<div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">{loading ? <div className="flex justify-center p-12 text-sm font-semibold text-slate-500"><Spinner /><span className="ml-3">Loading invoices</span></div> : pageData?.invoices.length ? <table className="w-full min-w-[900px] text-left text-sm"><thead className="border-b bg-slate-50 text-xs uppercase text-slate-500"><tr><th className="px-4 py-3">Number</th><th className="px-4 py-3">Type</th><th className="px-4 py-3">Date</th><th className="px-4 py-3">Total</th><th className="px-4 py-3">Balance</th><th className="px-4 py-3">Status</th><th className="px-4 py-3 text-right">Actions</th></tr></thead><tbody className="divide-y">{pageData.invoices.map((invoice) => <tr key={invoice.id}><td className="px-4 py-4 font-semibold">{invoice.invoiceNumber}</td><td className="px-4 py-4">{invoice.documentType}</td><td className="px-4 py-4">{invoice.invoiceDate}</td><td className="px-4 py-4">{Number(invoice.grandTotal).toLocaleString()}</td><td className="px-4 py-4">{Number(invoice.balanceDue).toLocaleString()}</td><td className="px-4 py-4"><span className="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">{invoice.status}</span></td><td className="px-4 py-4"><div className="flex justify-end gap-2"><Link className="font-semibold text-blue-700" to={`/invoices/${invoice.id}`}>View</Link>{invoice.status === 'draft' ? <button className="font-semibold text-emerald-700" onClick={() => void issue(invoice)} type="button">Issue</button> : null}<Link className="font-semibold text-slate-700" to={`/invoices/${invoice.id}/edit`}>Edit</Link></div></td></tr>)}</tbody></table> : <div className="p-12 text-center text-sm text-slate-500">No invoices found.</div>}</div>{pageData ? <div className="flex items-center justify-between text-sm text-slate-500"><span>{pageData.meta.total} total</span><div className="flex gap-2"><Button disabled={pageData.meta.currentPage <= 1 || loading} onClick={() => setPage((value) => value - 1)} variant="secondary">Previous</Button><span className="flex items-center px-2">Page {pageData.meta.currentPage} of {Math.max(1, pageData.meta.lastPage)}</span><Button disabled={pageData.meta.currentPage >= pageData.meta.lastPage || loading} onClick={() => setPage((value) => value + 1)} variant="secondary">Next</Button></div></div> : null}</div>;
}
