import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import { Spinner } from '../../../shared/components/ui/Spinner';
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
            <header className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div><p className="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Master data</p><h1 className="mt-1 text-3xl font-bold text-slate-950">Units of measure</h1><p className="mt-1 text-sm text-slate-500">Reusable quantity units for Items and future transaction flows.</p></div>
                <Link className="inline-flex h-10 items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700" to="/uoms/new">Create UOM</Link>
            </header>
            <div className="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_180px]">
                <Input placeholder="Search by code, name, or symbol" value={searchInput} onChange={(event) => setSearchInput(event.target.value)} />
                <select className="h-11 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm" value={status} onChange={(event) => { setPage(1); setStatus(event.target.value as UomStatus | ''); }}>
                    <option value="">All statuses</option><option value="active">Active</option><option value="inactive">Inactive</option>
                </select>
            </div>
            {error ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div> : null}
            <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                {loading ? <div className="flex items-center justify-center p-12 text-sm font-semibold text-slate-500"><Spinner /><span className="ml-3">Loading units</span></div> : pageData?.items.length ? (
                    <div className="overflow-x-auto"><table className="w-full min-w-[720px] text-left text-sm">
                        <thead className="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th className="px-4 py-3">Code</th><th className="px-4 py-3">Name</th><th className="px-4 py-3">Precision</th><th className="px-4 py-3">Base</th><th className="px-4 py-3">Status</th><th className="px-4 py-3 text-right">Actions</th></tr></thead>
                        <tbody className="divide-y divide-slate-100">{pageData.items.map((uom) => <tr className="hover:bg-slate-50/70" key={uom.id}>
                            <td className="px-4 py-4 font-semibold text-slate-900">{uom.uomCode}</td><td className="px-4 py-4"><p className="font-semibold text-slate-900">{uom.name}</p><p className="text-xs text-slate-500">{uom.symbol || 'No symbol'}</p></td><td className="px-4 py-4 text-slate-600">{uom.decimalPrecision}</td><td className="px-4 py-4 text-slate-600">{uom.isBase ? 'Yes' : 'No'}</td><td className="px-4 py-4"><Status value={uom.status} /></td>
                            <td className="px-4 py-4"><div className="flex justify-end gap-2"><Link className="rounded-md px-2 py-1.5 font-semibold text-blue-700 hover:bg-blue-50" to={`/uoms/${uom.id}`}>View</Link><Link className="rounded-md px-2 py-1.5 font-semibold text-slate-700 hover:bg-slate-100" to={`/uoms/${uom.id}/edit`}>Edit</Link><button className="rounded-md px-2 py-1.5 font-semibold text-red-600 hover:bg-red-50" onClick={() => void remove(uom)} type="button">Delete</button></div></td>
                        </tr>)}</tbody>
                    </table></div>
                ) : <div className="p-12 text-center"><p className="font-semibold text-slate-800">No units found</p><p className="mt-1 text-sm text-slate-500">Adjust the filters or create the first unit.</p></div>}
            </div>
            {pageData ? <div className="flex items-center justify-between text-sm text-slate-500"><span>{pageData.meta.total} total</span><div className="flex gap-2"><Button disabled={pageData.meta.currentPage <= 1 || loading} onClick={() => setPage((current) => current - 1)} variant="secondary">Previous</Button><span className="flex items-center px-2">Page {pageData.meta.currentPage} of {Math.max(1, pageData.meta.lastPage)}</span><Button disabled={pageData.meta.currentPage >= pageData.meta.lastPage || loading} onClick={() => setPage((current) => current + 1)} variant="secondary">Next</Button></div></div> : null}
        </div>
    );
}

function Status({ value }: { value: UomStatus }) {
    return <span className={`rounded-full px-2.5 py-1 text-xs font-bold ${value === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}>{value}</span>;
}
