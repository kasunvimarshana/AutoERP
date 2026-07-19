import { useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Modal } from '@/shared/components/Modal';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { Tabs } from '@/shared/components/Tabs';
import { useApi } from '@/shared/hooks/useApi';
import { notifySuccess } from '@/shared/notifications/appToast';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { formatDate } from '@/shared/utils/formatDate';
import { readableRelation } from '@/shared/utils/object';
import { statusBadgeClassName } from '@/shared/components/StatusBadge';
import { listVehicleServiceJobs } from '../vehicleServiceApi';
import { vehicleServicePermissions } from '../vehicleServicePermissions';
import type { VehicleServiceJob } from '../vehicleServiceTypes';
import VehicleServiceCommissionSettingsPanel from './VehicleServiceCommissionSettingsPanel';
import { VehicleServiceStatusBadge } from '../components/VehicleServiceStatusBadge';

const editableStatuses = ['draft', 'inspected', 'in_progress'] as const;
const statuses = ['draft', 'inspected', 'in_progress', 'completed', 'invoiced', 'partially_paid', 'paid', 'cancelled'] as const;
type StatusFilter = '' | (typeof statuses)[number];
type PriorityStatus = typeof editableStatuses[number];
const statusOptions = statuses.map((value) => ({ value, label: value.replaceAll('_', ' ') }));
const priorityStatuses: PriorityStatus[] = ['draft', 'inspected', 'in_progress'];
const statusTabs: Array<{ id: StatusFilter; label: string }> = [
    { id: '', label: 'All' },
    { id: 'inspected', label: 'Inspected' },
    { id: 'draft', label: 'Draft' },
    { id: 'in_progress', label: 'In progress' },
    { id: 'completed', label: 'Completed' },
    { id: 'invoiced', label: 'Invoiced' },
    { id: 'partially_paid', label: 'Partially paid' },
    { id: 'paid', label: 'Paid' },
    { id: 'cancelled', label: 'Cancelled' },
];

export default function VehicleServiceJobListPage() {
    const auth = useAuth();
    const canCreate = hasPermission(auth, vehicleServicePermissions.jobsCreate);
    const canUpdate = hasPermission(auth, vehicleServicePermissions.jobsUpdate);
    const canViewCommissions = hasPermission(auth, vehicleServicePermissions.commissionsView);
    const [searchParams, setSearchParams] = useSearchParams();
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState<StatusFilter>(resolveStatusFilter(searchParams.get('status')));
    const [dateFrom, setDateFrom] = useState('');
    const [dateTo, setDateTo] = useState('');
    const [page, setPage] = useState(1);
    const [showCommissionDefaults, setShowCommissionDefaults] = useState(false);
    const debounced = useDebounce(search);
    const result = useApi((signal) => listVehicleServiceJobs({
        search: debounced || undefined,
        status: status || undefined,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
        page,
        per_page: 25,
    }, signal), [debounced, status, dateFrom, dateTo, page]);
    const priorityCounts = useApi(async (signal) => {
        const countEntries = await Promise.all(priorityStatuses.map(async (priorityStatus) => {
            const response = await listVehicleServiceJobs({
                search: debounced || undefined,
                status: priorityStatus,
                date_from: dateFrom || undefined,
                date_to: dateTo || undefined,
                page: 1,
                per_page: 1,
            }, signal);

            return [priorityStatus, response.meta?.total ?? 0] as const;
        }));

        return Object.fromEntries(countEntries) as Record<PriorityStatus, number>;
    }, [debounced, dateFrom, dateTo], true, false);
    const tabsWithCounts = statusTabs.map((tab) => ({
        ...tab,
        label: renderStatusTabLabel(tab.label, tab.id, priorityCounts.data),
    }));
    const columns: DataColumn<VehicleServiceJob>[] = [
        { key: 'number', header: 'Job', render: (job) => <Link className="font-semibold text-sky-700 hover:underline" to={`/vehicle-service/jobs/${job.id}`}>{job.job_number}</Link> },
        { key: 'date', header: 'Date', render: (job) => formatDate(job.job_date) },
        { key: 'customer', header: 'Customer', render: (job) => readableRelation(job.customer) },
        { key: 'vehicle', header: 'Vehicle', render: (job) => readableRelation(job.vehicle) },
        { key: 'total', header: 'Total', render: (job) => <MoneyDisplay value={job.grand_total} /> },
        { key: 'status', header: 'Status', render: (job) => <VehicleServiceStatusBadge status={job.status} /> },
        {
            key: 'actions',
            header: '',
            render: (job) => (
                <div className="flex items-center gap-2">
                    <LinkButton to={`/vehicle-service/jobs/${job.id}`} variant="ghost">View</LinkButton>
                    {canUpdate && editableStatuses.includes(job.status as typeof editableStatuses[number]) && (
                        <Link
                            to={`/vehicle-service/jobs/${job.id}/edit`}
                            className="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-sky-700 transition hover:border-sky-200 hover:bg-sky-50"
                            aria-label="Edit job"
                            title="Edit job"
                        >
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" strokeWidth="1.9" className="h-5 w-5" aria-hidden="true">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 13.75v2.5h2.5L14.5 8l-2.5-2.5-8.25 8.25Z" />
                                <path strokeLinecap="round" strokeLinejoin="round" d="m10.75 4.75 2.5 2.5" />
                            </svg>
                        </Link>
                    )}
                </div>
            ),
        },
    ];

    return (
        <>
            <ContentHeader
                title="Vehicle service jobs"
                description="Service workflow, mixed job lines, workforce, stock, invoicing, and payment preparation."
                actions={(
                    <div className="flex flex-wrap gap-2">
                        {canViewCommissions && (
                            <Button type="button" variant="secondary" onClick={() => setShowCommissionDefaults(true)}>
                                Commission defaults
                            </Button>
                        )}
                        {canCreate && <LinkButton to="/vehicle-service/jobs/create">New service job</LinkButton>}
                    </div>
                )}
            />
            {canViewCommissions && (
                <Modal
                    open={showCommissionDefaults}
                    title="Commission defaults"
                    onClose={() => setShowCommissionDefaults(false)}
                >
                    <VehicleServiceCommissionSettingsPanel
                        onSaved={() => {
                            notifySuccess('Commission defaults updated for new vehicle service jobs.', 'Commission defaults saved');
                            setShowCommissionDefaults(false);
                        }}
                    />
                </Modal>
            )}
            <div className="mb-4 rounded-xl border border-slate-200 bg-white">
                <Tabs
                    id="vehicle-service-job-status-tabs"
                    tabs={tabsWithCounts}
                    active={status}
                    onChange={(nextStatus) => {
                        setStatus(nextStatus);
                        setPage(1);
                        updateStatusSearchParam(nextStatus, setSearchParams, searchParams);
                    }}
                />
            </div>
            <div className="mb-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Input label="Search" type="search" placeholder="Job, customer, or vehicle" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
                <Select
                    label="Status"
                    value={status}
                    options={[{ value: '', label: 'all' }, ...statusOptions]}
                    onChange={(event) => {
                        const nextStatus = resolveStatusFilter(event.target.value);
                        setStatus(nextStatus);
                        setPage(1);
                        updateStatusSearchParam(nextStatus, setSearchParams, searchParams);
                    }}
                />
                <Input label="From" type="date" value={dateFrom} onChange={(event) => { setDateFrom(event.target.value); setPage(1); }} />
                <Input label="To" type="date" value={dateTo} onChange={(event) => { setDateTo(event.target.value); setPage(1); }} />
            </div>
            <ErrorAlert error={result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(job) => job.id} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}

function resolveStatusFilter(value: string | null): StatusFilter {
    if (value === null || value === '') return '';
    return statuses.includes(value as (typeof statuses)[number]) ? value as StatusFilter : '';
}

function updateStatusSearchParam(
    status: StatusFilter,
    setSearchParams: ReturnType<typeof useSearchParams>[1],
    currentParams: URLSearchParams,
) {
    const nextParams = new URLSearchParams(currentParams);
    if (status === '') nextParams.delete('status');
    else nextParams.set('status', status);
    setSearchParams(nextParams, { replace: true });
}

function renderStatusTabLabel(
    label: string,
    status: StatusFilter,
    counts: Record<PriorityStatus, number> | null,
) {
    const count = status !== '' && priorityStatuses.includes(status as PriorityStatus)
        ? counts?.[status as PriorityStatus] ?? 0
        : 0;

    return (
        <span className="relative inline-flex items-center pr-3">
            <span>{label}</span>
            {count > 0 ? (
                <span className={`absolute -right-2 -top-2 inline-flex min-w-5 items-center justify-center rounded-full px-1.5 py-0.5 text-[11px] font-semibold leading-none shadow-sm ${statusBadgeClassName(status)}`}>
                    {count > 9 ? '9+' : count}
                </span>
            ) : null}
        </span>
    );
}
