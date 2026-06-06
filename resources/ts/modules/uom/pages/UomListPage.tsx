import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { EmptyState, FilterCard, LoadingState, PageHeader, Pagination, PrimaryLink, StatusBadge, TableCard } from '../../../shared/components/erp/ErpUi';
import { Input } from '../../../shared/components/ui/Input';
import { uomApi } from '../services/uomApi';
import type { UomListItem, UomPage, UomStatus } from '../types/uom.types';

export function UomListPage() {
    const [pageData, setPageData] = useState<UomPage | null>(null);
    const [searchInput, setSearchInput] = useState('');
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState<UomStatus | ''>('');
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
        void uomApi.list({ page, perPage: 20, search: search || undefined, status: status || undefined })
            .then((response) => {
                if (active) setPageData(response);
            })
            .catch((requestError) => {
                if (active) setError(requestError instanceof Error ? requestError.message : 'Unable to load units of measure.');
            })
            .finally(() => {
                if (active) setLoading(false);
            });
        return () => {
            active = false;
        };
    }, [page, search, status]);

    async function remove(uom: UomListItem) {
        if (!window.confirm(`Delete ${uom.name}? Existing records keep their references.`)) return;
        try {
            await uomApi.remove(uom.id);
            setPageData((current) => current ? {
                ...current,
                items: current.items.filter((candidate) => candidate.id !== uom.id),
                meta: { ...current.meta, total: Math.max(0, current.meta.total - 1) },
            } : current);
        } catch (requestError) {
            setError(requestError instanceof Error ? requestError.message : 'Unable to delete this unit.');
        }
    }

    return (
        <div className="space-y-5">
            <PageHeader actions={<PrimaryLink to="/uoms/new">Create UOM</PrimaryLink>} eyebrow="Master data" subtitle="Maintain reusable quantity units and decimal precision for item transactions." title="Units of measure" />
            <FilterCard className="sm:grid-cols-[1fr_180px]">
                <Input placeholder="Search by code, name, or symbol" value={searchInput} onChange={(event) => setSearchInput(event.target.value)} />
                <select className="erp-select" value={status} onChange={(event) => { setPage(1); setStatus(event.target.value as UomStatus | ''); }}>
                    <option value="">All statuses</option><option value="active">Active</option><option value="inactive">Inactive</option>
                </select>
            </FilterCard>
            {error ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div> : null}
            <TableCard>
                {loading ? <LoadingState label="Loading units of measure" /> : pageData?.items.length ? (
                    <div className="overflow-x-auto"><table className="w-full min-w-[720px] text-left text-sm">
                        <thead className="border-b border-slate-200 bg-slate-50/80 text-xs uppercase tracking-wide text-slate-500"><tr><th className="px-4 py-3">Code</th><th className="px-4 py-3">Name</th><th className="px-4 py-3">Precision</th><th className="px-4 py-3">Base</th><th className="px-4 py-3">Status</th><th className="px-4 py-3 text-right">Actions</th></tr></thead>
                        <tbody className="divide-y divide-slate-100">{pageData.items.map((uom) => <tr className="transition hover:bg-slate-50/70" key={uom.id}>
                            <td className="px-4 py-4 font-semibold text-slate-900">{uom.uomCode}</td><td className="px-4 py-4"><p className="font-semibold text-slate-900">{uom.name}</p><p className="text-xs text-slate-500">{uom.symbol || 'No symbol'}</p></td><td className="px-4 py-4 text-slate-600">{uom.decimalPrecision}</td><td className="px-4 py-4 text-slate-600">{uom.isBase ? 'Yes' : 'No'}</td><td className="px-4 py-4"><StatusBadge value={uom.status} /></td>
                            <td className="px-4 py-4"><div className="flex justify-end gap-1"><Link className="rounded-md px-2 py-1.5 font-semibold text-blue-700 hover:bg-blue-50" to={`/uoms/${uom.id}`}>View</Link><Link className="rounded-md px-2 py-1.5 font-semibold text-slate-700 hover:bg-slate-100" to={`/uoms/${uom.id}/edit`}>Edit</Link><button className="rounded-md px-2 py-1.5 font-semibold text-red-600 hover:bg-red-50" onClick={() => void remove(uom)} type="button">Delete</button></div></td>
                        </tr>)}</tbody>
                    </table></div>
                ) : <EmptyState action={<PrimaryLink to="/uoms/new">Create UOM</PrimaryLink>} title="No units found" />}
            </TableCard>
            {pageData ? <Pagination current={pageData.meta.currentPage} last={pageData.meta.lastPage} loading={loading} onPage={setPage} total={pageData.meta.total} /> : null}
        </div>
    );
}
