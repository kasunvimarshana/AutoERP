import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import { Spinner } from '../../../shared/components/ui/Spinner';
import { itemApi } from '../services/itemApi';
import type { ItemListItem, ItemPage, ItemStatus } from '../types/item.types';

export function ItemListPage() {
    const [pageData, setPageData] = useState<ItemPage | null>(null);
    const [searchInput, setSearchInput] = useState('');
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState<ItemStatus | ''>('');
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
        void itemApi.list({ page, perPage: 20, search: search || undefined, status: status || undefined })
            .then((response) => {
                if (active) setPageData(response);
            })
            .catch((requestError) => {
                if (active) setError(requestError instanceof Error ? requestError.message : 'Unable to load items.');
            })
            .finally(() => {
                if (active) setLoading(false);
            });

        return () => {
            active = false;
        };
    }, [page, search, status]);

    async function remove(item: ItemListItem) {
        if (!window.confirm(`Delete ${item.name}? This action soft-deletes the record.`)) return;
        try {
            await itemApi.remove(item.id);
            setPageData((current) => current ? {
                ...current,
                items: current.items.filter((candidate) => candidate.id !== item.id),
                meta: { ...current.meta, total: Math.max(0, current.meta.total - 1) },
            } : current);
        } catch (requestError) {
            setError(requestError instanceof Error ? requestError.message : 'Unable to delete this item.');
        }
    }

    return <div className="space-y-5">
        <header className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><p className="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Master data</p><h1 className="mt-1 text-3xl font-bold text-slate-950">Items</h1><p className="mt-1 text-sm text-slate-500">Product and service definitions ready for future transaction flows.</p></div><Link className="inline-flex h-10 items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700" to="/items/new">Create item</Link></header>
        <div className="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_180px]"><Input placeholder="Search by code, name, SKU, or barcode" value={searchInput} onChange={(event) => setSearchInput(event.target.value)} /><select className="h-11 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm" value={status} onChange={(event) => { setPage(1); setStatus(event.target.value as ItemStatus | ''); }}><option value="">All statuses</option><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
        {error ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div> : null}
        <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">{loading ? <div className="flex items-center justify-center p-12 text-sm font-semibold text-slate-500"><Spinner /><span className="ml-3">Loading items</span></div> : pageData?.items.length ? <div className="overflow-x-auto"><table className="w-full min-w-[900px] text-left text-sm"><thead className="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th className="px-4 py-3">Code</th><th className="px-4 py-3">Item</th><th className="px-4 py-3">Base UOM</th><th className="px-4 py-3">Prices</th><th className="px-4 py-3">Status</th><th className="px-4 py-3 text-right">Actions</th></tr></thead><tbody className="divide-y divide-slate-100">{pageData.items.map((item) => <tr className="hover:bg-slate-50/70" key={item.id}><td className="px-4 py-4 font-semibold text-slate-900">{item.itemCode}</td><td className="px-4 py-4"><p className="font-semibold text-slate-900">{item.name}</p><p className="text-xs text-slate-500">{item.sku || item.displayName || 'No SKU'}</p></td><td className="px-4 py-4 text-slate-600">{item.baseUom.uomCode}</td><td className="px-4 py-4 text-slate-600"><p>Sales {Number(item.salesPrice).toLocaleString()}</p><p className="text-xs">Cost {Number(item.costPrice).toLocaleString()}</p></td><td className="px-4 py-4"><span className={`rounded-full px-2.5 py-1 text-xs font-bold ${item.status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}>{item.status}</span></td><td className="px-4 py-4"><div className="flex justify-end gap-2"><Link className="rounded-md px-2 py-1.5 font-semibold text-blue-700 hover:bg-blue-50" to={`/items/${item.id}`}>View</Link><Link className="rounded-md px-2 py-1.5 font-semibold text-slate-700 hover:bg-slate-100" to={`/items/${item.id}/edit`}>Edit</Link><button className="rounded-md px-2 py-1.5 font-semibold text-red-600 hover:bg-red-50" onClick={() => void remove(item)} type="button">Delete</button></div></td></tr>)}</tbody></table></div> : <div className="p-12 text-center"><p className="font-semibold text-slate-800">No items found</p><p className="mt-1 text-sm text-slate-500">Adjust the filters or create the first item.</p></div>}</div>
        {pageData ? <div className="flex items-center justify-between text-sm text-slate-500"><span>{pageData.meta.total} total</span><div className="flex gap-2"><Button disabled={pageData.meta.currentPage <= 1 || loading} onClick={() => setPage((current) => current - 1)} variant="secondary">Previous</Button><span className="flex items-center px-2">Page {pageData.meta.currentPage} of {Math.max(1, pageData.meta.lastPage)}</span><Button disabled={pageData.meta.currentPage >= pageData.meta.lastPage || loading} onClick={() => setPage((current) => current + 1)} variant="secondary">Next</Button></div></div> : null}
    </div>;
}
