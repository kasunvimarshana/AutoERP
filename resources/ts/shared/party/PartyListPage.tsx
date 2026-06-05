import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Input';
import { Spinner } from '../components/ui/Spinner';
import type { PartyApi, PartyListItem, PartyPage, PartyStatus } from './party.types';

type PartyListPageProps = {
    api: PartyApi;
    basePath: string;
    noun: string;
    title: string;
};

export function PartyListPage({ api, basePath, noun, title }: PartyListPageProps) {
    const [pageData, setPageData] = useState<PartyPage | null>(null);
    const [searchInput, setSearchInput] = useState('');
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState<PartyStatus | ''>('');
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

        void api
            .list({ page, perPage: 20, search: search || undefined, status: status || undefined })
            .then((response) => {
                if (active) setPageData(response);
            })
            .catch((requestError) => {
                if (active) setError(requestError instanceof Error ? requestError.message : `Unable to load ${title.toLowerCase()}.`);
            })
            .finally(() => {
                if (active) setLoading(false);
            });

        return () => {
            active = false;
        };
    }, [api, page, search, status, title]);

    async function remove(item: PartyListItem) {
        if (!window.confirm(`Delete ${item.name}? This action soft-deletes the record.`)) return;

        try {
            await api.remove(item.id);
            setPageData((current) =>
                current
                    ? {
                          ...current,
                          items: current.items.filter((candidate) => candidate.id !== item.id),
                          meta: { ...current.meta, total: Math.max(0, current.meta.total - 1) },
                      }
                    : current,
            );
        } catch (requestError) {
            setError(requestError instanceof Error ? requestError.message : `Unable to delete this ${noun.toLowerCase()}.`);
        }
    }

    return (
        <div className="space-y-5">
            <header className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p className="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Master data</p>
                    <h1 className="mt-1 text-3xl font-bold text-slate-950">{title}</h1>
                    <p className="mt-1 text-sm text-slate-500">Tenant-scoped records with paginated search and local delete updates.</p>
                </div>
                <Link className="inline-flex h-10 items-center justify-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700" to={`${basePath}/new`}>
                    Create {noun.toLowerCase()}
                </Link>
            </header>

            <div className="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_180px]">
                <Input placeholder={`Search ${title.toLowerCase()} by code, name, email, or phone`} value={searchInput} onChange={(event) => setSearchInput(event.target.value)} />
                <select className="h-11 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm" value={status} onChange={(event) => { setPage(1); setStatus(event.target.value as PartyStatus | ''); }}>
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            {error ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div> : null}

            <div className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                {loading ? (
                    <div className="flex items-center justify-center p-12 text-sm font-semibold text-slate-500"><Spinner /><span className="ml-3">Loading {title.toLowerCase()}</span></div>
                ) : pageData?.items.length ? (
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[760px] text-left text-sm">
                            <thead className="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                                <tr><th className="px-4 py-3">Code</th><th className="px-4 py-3">Name</th><th className="px-4 py-3">Contact</th><th className="px-4 py-3">Credit limit</th><th className="px-4 py-3">Status</th><th className="px-4 py-3 text-right">Actions</th></tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {pageData.items.map((item) => (
                                    <tr className="hover:bg-slate-50/70" key={item.id}>
                                        <td className="px-4 py-4 font-semibold text-slate-900">{item.code}</td>
                                        <td className="px-4 py-4"><p className="font-semibold text-slate-900">{item.name}</p><p className="text-xs text-slate-500">{item.displayName || 'No display name'}</p></td>
                                        <td className="px-4 py-4 text-slate-600"><p>{item.email || 'No email'}</p><p className="text-xs">{item.mobile || item.phone || 'No phone'}</p></td>
                                        <td className="px-4 py-4 tabular-nums text-slate-700">{Number(item.creditLimit).toLocaleString(undefined, { maximumFractionDigits: 4 })}</td>
                                        <td className="px-4 py-4"><span className={`rounded-full px-2.5 py-1 text-xs font-bold ${item.status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}>{item.status}</span></td>
                                        <td className="px-4 py-4"><div className="flex justify-end gap-2"><Link className="rounded-md px-2 py-1.5 font-semibold text-blue-700 hover:bg-blue-50" to={`${basePath}/${item.id}`}>View</Link><Link className="rounded-md px-2 py-1.5 font-semibold text-slate-700 hover:bg-slate-100" to={`${basePath}/${item.id}/edit`}>Edit</Link><button className="rounded-md px-2 py-1.5 font-semibold text-red-600 hover:bg-red-50" onClick={() => void remove(item)} type="button">Delete</button></div></td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ) : (
                    <div className="p-12 text-center"><p className="font-semibold text-slate-800">No {title.toLowerCase()} found</p><p className="mt-1 text-sm text-slate-500">Adjust the filters or create the first record.</p></div>
                )}
            </div>

            {pageData ? (
                <div className="flex items-center justify-between text-sm text-slate-500">
                    <span>{pageData.meta.total} total</span>
                    <div className="flex gap-2"><Button disabled={pageData.meta.currentPage <= 1 || loading} onClick={() => setPage((current) => current - 1)} variant="secondary">Previous</Button><span className="flex items-center px-2">Page {pageData.meta.currentPage} of {Math.max(1, pageData.meta.lastPage)}</span><Button disabled={pageData.meta.currentPage >= pageData.meta.lastPage || loading} onClick={() => setPage((current) => current + 1)} variant="secondary">Next</Button></div>
                </div>
            ) : null}
        </div>
    );
}
