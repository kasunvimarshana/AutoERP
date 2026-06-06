import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { EmptyState, FilterCard, LoadingState, MoneyDisplay, PageHeader, Pagination, PrimaryLink, SecondaryLink, StatCard, StatusBadge, TableCard } from '../../../shared/components/erp/ErpUi';
import { Input } from '../../../shared/components/ui/Input';
import { vehicleServiceApi } from '../services/vehicleServiceApi';
import type { Dashboard, JobCard, Page } from '../types/vehicleService.types';

export function JobCardListPage() {
    const [data, setData] = useState<Page<JobCard>>();
    const [dashboard, setDashboard] = useState<Dashboard>();
    const [searchInput, setSearchInput] = useState('');
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
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
        void vehicleServiceApi.listJobs({ page, perPage: 20, search: search || undefined, status: status || undefined })
            .then((result) => {
                if (active) setData(result);
            })
            .catch((requestError) => {
                if (active) setError(requestError instanceof Error ? requestError.message : 'Unable to load jobs.');
            })
            .finally(() => {
                if (active) setLoading(false);
            });
        return () => {
            active = false;
        };
    }, [page, search, status]);

    useEffect(() => {
        void vehicleServiceApi.dashboard().then(setDashboard).catch(() => undefined);
    }, []);

    async function remove(job: JobCard) {
        if (!window.confirm(`Delete ${job.jobCardNumber}?`)) return;
        try {
            await vehicleServiceApi.removeJob(job.id);
            setData((current) => current ? { ...current, items: current.items.filter((item) => item.id !== job.id), meta: { ...current.meta, total: current.meta.total - 1 } } : current);
        } catch (requestError) {
            setError(requestError instanceof Error ? requestError.message : 'Unable to delete job.');
        }
    }

    return (
        <div className="space-y-5">
            <PageHeader actions={<><SecondaryLink to="/vehicle-service/types">Service types</SecondaryLink><PrimaryLink to="/vehicle-service/jobs/new">Create job</PrimaryLink></>} eyebrow="Operations" subtitle="Manage customer service work, parts consumption, labor, invoicing, and settlement visibility." title="Vehicle service jobs" />
            {dashboard ? <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"><StatCard label="Open jobs" value={dashboard.open_jobs} /><StatCard label="Completed" value={dashboard.completed_jobs} /><StatCard label="Pending invoice" value={dashboard.pending_invoice_jobs} /><StatCard label="Unpaid" value={<MoneyDisplay value={dashboard.unpaid_amount} />} /></div> : null}
            <FilterCard className="sm:grid-cols-[1fr_190px]">
                <Input placeholder="Search job, customer, or vehicle" value={searchInput} onChange={(event) => setSearchInput(event.target.value)} />
                <select className="erp-select" value={status} onChange={(event) => { setPage(1); setStatus(event.target.value); }}><option value="">All statuses</option><option value="open">Open</option><option value="in_progress">In progress</option><option value="completed">Completed</option><option value="invoiced">Invoiced</option><option value="cancelled">Cancelled</option></select>
            </FilterCard>
            {error ? <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{error}</div> : null}
            <TableCard>
                {loading ? <LoadingState label="Loading service jobs" /> : data?.items.length ? (
                    <div className="overflow-x-auto"><table className="w-full min-w-[920px] text-left text-sm">
                        <thead className="border-b border-slate-200 bg-slate-50/80 text-xs uppercase tracking-wide text-slate-500"><tr><th className="px-4 py-3">Job</th><th className="px-4 py-3">Customer</th><th className="px-4 py-3">Vehicle</th><th className="px-4 py-3">Type</th><th className="px-4 py-3">Total</th><th className="px-4 py-3">Status</th><th className="px-4 py-3 text-right">Actions</th></tr></thead>
                        <tbody className="divide-y divide-slate-100">{data.items.map((job) => <tr className="transition hover:bg-slate-50/70" key={job.id}><td className="px-4 py-4 font-semibold text-slate-900">{job.jobCardNumber}</td><td className="px-4 py-4">{job.customerName || '-'}</td><td className="px-4 py-4">{job.registrationNumber || '-'}</td><td className="px-4 py-4">{job.serviceTypeName || '-'}</td><td className="px-4 py-4 font-semibold"><MoneyDisplay value={job.grandTotal} /></td><td className="px-4 py-4"><StatusBadge value={job.status} /></td><td className="px-4 py-4 text-right"><Link className="mr-3 font-semibold text-blue-700" to={`/vehicle-service/jobs/${job.id}`}>View</Link>{['open', 'in_progress'].includes(job.status) ? <Link className="mr-3 font-semibold text-slate-700" to={`/vehicle-service/jobs/${job.id}/edit`}>Edit</Link> : null}{job.status === 'open' ? <button className="font-semibold text-red-600" onClick={() => void remove(job)} type="button">Delete</button> : null}</td></tr>)}</tbody>
                    </table></div>
                ) : <EmptyState action={<PrimaryLink to="/vehicle-service/jobs/new">Create job</PrimaryLink>} title="No job cards found" />}
            </TableCard>
            {data ? <Pagination current={data.meta.currentPage} last={data.meta.lastPage} loading={loading} onPage={setPage} total={data.meta.total} /> : null}
        </div>
    );
}

export function Badge({ value }: { value: string }) {
    return <StatusBadge value={value} />;
}
