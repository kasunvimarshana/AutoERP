import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { EmptyState, FilterCard, LoadingState, MoneyDisplay, PageHeader, Pagination, PrimaryLink, StatusBadge, TableCard } from '../../../shared/components/erp/ErpUi';
import { Input } from '../../../shared/components/ui/Input';
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

    return (
        <div className="space-y-5">
            <PageHeader actions={<PrimaryLink to="/items/new">Create item</PrimaryLink>} eyebrow="Master data" subtitle="Manage products, services, units, pricing, and inventory behavior." title="Items" />
            <FilterCard className="sm:grid-cols-[1fr_180px]">
                <Input placeholder="Search by code, name, SKU, or barcode" value={searchInput} onChange={(event) => setSearchInput(event.target.value)} />
                <select className="erp-select" value={status} onChange={(event) => { setPage(1); setStatus(event.target.value as ItemStatus | ''); }}>
                    <option value="">All statuses</option><option value="active">Active</option><option value="inactive">Inactive</option>
                </select>
            </FilterCard>
            {error ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div> : null}
            <TableCard>
                {loading ? <LoadingState label="Loading items" /> : pageData?.items.length ? (
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[900px] text-left text-sm">
                            <thead className="border-b border-slate-200 bg-slate-50/80 text-xs uppercase tracking-wide text-slate-500"><tr><th className="px-4 py-3">Code</th><th className="px-4 py-3">Item</th><th className="px-4 py-3">Base UOM</th><th className="px-4 py-3">Prices</th><th className="px-4 py-3">Status</th><th className="px-4 py-3 text-right">Actions</th></tr></thead>
                            <tbody className="divide-y divide-slate-100">
                                {pageData.items.map((item) => (
                                    <tr className="transition hover:bg-slate-50/70" key={item.id}>
                                        <td className="px-4 py-4 font-semibold text-slate-900">{item.itemCode}</td>
                                        <td className="px-4 py-4"><p className="font-semibold text-slate-900">{item.name}</p><p className="text-xs text-slate-500">{item.sku || item.displayName || 'No SKU'}</p></td>
                                        <td className="px-4 py-4 text-slate-600">{item.baseUom.uomCode}</td>
                                        <td className="px-4 py-4 text-slate-600"><p>Sales <MoneyDisplay value={item.salesPrice} /></p><p className="text-xs">Cost <MoneyDisplay value={item.costPrice} /></p></td>
                                        <td className="px-4 py-4"><StatusBadge value={item.status} /></td>
                                        <td className="px-4 py-4"><div className="flex justify-end gap-1"><Link className="rounded-md px-2 py-1.5 font-semibold text-blue-700 hover:bg-blue-50" to={`/items/${item.id}`}>View</Link><Link className="rounded-md px-2 py-1.5 font-semibold text-slate-700 hover:bg-slate-100" to={`/items/${item.id}/edit`}>Edit</Link><button className="rounded-md px-2 py-1.5 font-semibold text-red-600 hover:bg-red-50" onClick={() => void remove(item)} type="button">Delete</button></div></td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ) : <EmptyState action={<PrimaryLink to="/items/new">Create item</PrimaryLink>} title="No items found" />}
            </TableCard>
            {pageData ? <Pagination current={pageData.meta.currentPage} last={pageData.meta.lastPage} loading={loading} onPage={setPage} total={pageData.meta.total} /> : null}
        </div>
    );
}
