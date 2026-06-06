import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import { purchaseApi } from '../services/purchaseApi';
import type { PurchasePage, PurchaseReturn } from '../types/purchase.types';
import { Badge, Empty, Loading } from './PurchaseOrderListPage';
import { FilterCard, PageHeader, PrimaryLink, SecondaryLink } from '../../../shared/components/erp/ErpUi';

export function PurchaseReturnListPage() {
    const [data, setData] = useState<PurchasePage<PurchaseReturn> | null>(null);
    const [searchInput, setSearchInput] = useState('');
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    useEffect(() => { const timer = window.setTimeout(() => { setPage(1); setSearch(searchInput.trim()); }, 350); return () => window.clearTimeout(timer); }, [searchInput]);
    useEffect(() => { let active = true; setLoading(true); void purchaseApi.listReturns({ page, perPage: 20, search: search || undefined, status: status || undefined }).then((response) => { if (active) setData(response); }).catch((requestError) => { if (active) setError(requestError instanceof Error ? requestError.message : 'Unable to load returns.'); }).finally(() => { if (active) setLoading(false); }); return () => { active = false; }; }, [page, search, status]);
    return <div className="space-y-5"><PageHeader actions={<><SecondaryLink to="/purchase/grns">Goods receipts</SecondaryLink><PrimaryLink to="/purchase/returns/new">Create return</PrimaryLink></>} eyebrow="Operations" subtitle="Return received goods safely through the inventory issuing workflow." title="Purchase returns" /><FilterCard className="sm:grid-cols-[1fr_180px]"><Input placeholder="Search return, GRN, or supplier" value={searchInput} onChange={(event) => setSearchInput(event.target.value)} /><select className="erp-select" value={status} onChange={(event) => { setPage(1); setStatus(event.target.value); }}><option value="">All statuses</option><option value="draft">Draft</option><option value="posted">Posted</option><option value="refunded">Refunded</option></select></FilterCard>{error ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div> : null}<div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">{loading ? <Loading label="Loading returns" /> : data?.items.length ? <table className="w-full min-w-[760px] text-left text-sm"><thead className="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500"><tr><th className="px-4 py-3">Return</th><th className="px-4 py-3">Supplier</th><th className="px-4 py-3">Date</th><th className="px-4 py-3">Total</th><th className="px-4 py-3">Status</th><th className="px-4 py-3 text-right">Actions</th></tr></thead><tbody>{data.items.map((item) => <tr className="border-b border-slate-100 hover:bg-slate-50/70" key={item.id}><td className="px-4 py-3 font-semibold">{item.returnNumber}</td><td className="px-4 py-3">{item.supplierName}</td><td className="px-4 py-3">{item.returnDate}</td><td className="px-4 py-3">{Number(item.grandTotal).toLocaleString()}</td><td className="px-4 py-3"><Badge label={item.status} /></td><td className="px-4 py-3 text-right"><Link className="font-semibold text-blue-700" to={`/purchase/returns/${item.id}`}>View</Link></td></tr>)}</tbody></table> : <Empty label="No purchase returns found" />}</div>{data ? <div className="flex justify-end gap-2"><Button disabled={page <= 1 || loading} onClick={() => setPage((current) => current - 1)} variant="secondary">Previous</Button><Button disabled={page >= data.meta.lastPage || loading} onClick={() => setPage((current) => current + 1)} variant="secondary">Next</Button></div> : null}</div>;
}
