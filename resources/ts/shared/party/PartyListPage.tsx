import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ConfirmDialog } from '../components/erp/ConfirmDialog';
import { EmptyState, FilterCard, LoadingState, MoneyDisplay, PageHeader, Pagination, PrimaryLink, StatusBadge, TableCard } from '../components/erp/ErpUi';
import { Input } from '../components/ui/Input';
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
    const [confirming, setConfirming] = useState<PartyListItem | null>(null);
    const [deleting, setDeleting] = useState(false);

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
        void api.list({ page, perPage: 20, search: search || undefined, status: status || undefined })
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
        setDeleting(true);
        try {
            await api.remove(item.id);
            setPageData((current) => current ? {
                ...current,
                items: current.items.filter((candidate) => candidate.id !== item.id),
                meta: { ...current.meta, total: Math.max(0, current.meta.total - 1) },
            } : current);
            setConfirming(null);
        } catch (requestError) {
            setError(requestError instanceof Error ? requestError.message : `Unable to delete this ${noun.toLowerCase()}.`);
        } finally {
            setDeleting(false);
        }
    }

    return (
        <div className="space-y-5">
            <PageHeader
                actions={<PrimaryLink to={`${basePath}/new`}>Create {noun.toLowerCase()}</PrimaryLink>}
                eyebrow="Master data"
                subtitle={`Manage tenant-scoped ${title.toLowerCase()}, contact details, payment terms, and credit settings.`}
                title={title}
            />

            <FilterCard className="sm:grid-cols-[1fr_180px]">
                <Input placeholder={`Search ${title.toLowerCase()} by code, name, email, or phone`} value={searchInput} onChange={(event) => setSearchInput(event.target.value)} />
                <select className="erp-select" value={status} onChange={(event) => { setPage(1); setStatus(event.target.value as PartyStatus | ''); }}>
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </FilterCard>

            {error ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div> : null}

            <TableCard>
                {loading ? <LoadingState label={`Loading ${title.toLowerCase()}`} /> : pageData?.items.length ? (
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[760px] text-left text-sm">
                            <thead className="border-b border-slate-200 bg-slate-50/80 text-xs uppercase tracking-wide text-slate-500">
                                <tr><th className="px-4 py-3">Code</th><th className="px-4 py-3">Name</th><th className="px-4 py-3">Contact</th><th className="px-4 py-3">Credit limit</th><th className="px-4 py-3">Status</th><th className="px-4 py-3 text-right">Actions</th></tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {pageData.items.map((item) => (
                                    <tr className="transition hover:bg-slate-50/70" key={item.id}>
                                        <td className="px-4 py-4 font-semibold text-slate-900">{item.code}</td>
                                        <td className="px-4 py-4"><p className="font-semibold text-slate-900">{item.name}</p><p className="text-xs text-slate-500">{item.displayName || 'No display name'}</p></td>
                                        <td className="px-4 py-4 text-slate-600"><p>{item.email || 'No email'}</p><p className="text-xs">{item.mobile || item.phone || 'No phone'}</p></td>
                                        <td className="px-4 py-4 font-semibold text-slate-700"><MoneyDisplay value={item.creditLimit} /></td>
                                        <td className="px-4 py-4"><StatusBadge value={item.status} /></td>
                                        <td className="px-4 py-4"><div className="flex justify-end gap-1"><Link className="rounded-md px-2 py-1.5 font-semibold text-blue-700 hover:bg-blue-50" to={`${basePath}/${item.id}`}>View</Link><Link className="rounded-md px-2 py-1.5 font-semibold text-slate-700 hover:bg-slate-100" to={`${basePath}/${item.id}/edit`}>Edit</Link><button className="rounded-md px-2 py-1.5 font-semibold text-red-600 hover:bg-red-50" onClick={() => setConfirming(item)} type="button">Delete</button></div></td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ) : <EmptyState action={<PrimaryLink to={`${basePath}/new`}>Create {noun.toLowerCase()}</PrimaryLink>} title={`No ${title.toLowerCase()} found`} />}
            </TableCard>

            {pageData ? <Pagination current={pageData.meta.currentPage} last={pageData.meta.lastPage} loading={loading} onPage={setPage} total={pageData.meta.total} /> : null}
            <ConfirmDialog
                busy={deleting}
                confirmLabel={`Delete ${noun.toLowerCase()}`}
                onCancel={() => setConfirming(null)}
                onConfirm={() => confirming && void remove(confirming)}
                state={confirming ? { description: `${confirming.name} will be soft-deleted and removed from this list.`, title: `Delete ${noun.toLowerCase()}?` } : null}
            />
        </div>
    );
}
