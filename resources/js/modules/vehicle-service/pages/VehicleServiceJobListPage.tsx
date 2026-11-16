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
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { formatDate } from '@/shared/utils/formatDate';
import { readableRelation } from '@/shared/utils/object';
import { statusBadgeClassName } from '@/shared/components/StatusBadge';
import { getVehicleServiceJobBoard, listVehicleServiceJobs } from '../vehicleServiceApi';
import { vehicleServicePermissions } from '../vehicleServicePermissions';
import type { VehicleServiceJob, VehicleServiceJobBoardLane } from '../vehicleServiceTypes';
import { VehicleServiceJobBoard } from '../components/VehicleServiceJobBoard';
import VehicleServiceCommissionSettingsPanel from './VehicleServiceCommissionSettingsPanel';
import { VehicleServiceStatusBadge } from '../components/VehicleServiceStatusBadge';
import { ActionMenu } from '@/shared/components/ActionMenu';

const editableStatuses = ['draft', 'inspected', 'in_progress'] as const;
const statuses = ['draft', 'inspected', 'in_progress', 'completed', 'invoiced', 'partially_paid', 'paid', 'cancelled'] as const;
type StatusFilter = '' | (typeof statuses)[number];
type PriorityStatus = typeof editableStatuses[number];
const statusOptions = statuses.map((value) => ({ value, label: value.replaceAll('_', ' ') }));
const priorityStatuses: PriorityStatus[] = ['draft', 'inspected', 'in_progress'];
const initialBoardPages: Record<VehicleServiceJobBoardLane, number> = { pending: 1, in_progress: 1, completed: 1, billing_closed: 1 };
type BoardDatePreset = 'today' | 'week' | 'last_week' | 'all' | 'custom';
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
    const view = searchParams.get('view') === 'list' ? 'list' : 'board';
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState<StatusFilter>(resolveStatusFilter(searchParams.get('status')));
    const [dateFrom, setDateFrom] = useState('');
    const [dateTo, setDateTo] = useState('');
    const [page, setPage] = useState(1);
    const [boardPages, setBoardPages] = useState(initialBoardPages);
    const [boardDatePreset, setBoardDatePreset] = useState<BoardDatePreset>('all');
    const [showCommissionDefaults, setShowCommissionDefaults] = useState(false);
    const [filtersOpen, setFiltersOpen] = useState(false);
    const debounced = useDebounce(search);
    const validDateRange = !dateFrom || !dateTo || dateFrom <= dateTo;
    const result = useApi((signal) => listVehicleServiceJobs({
        search: debounced || undefined,
        status: status || undefined,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
        page,
        per_page: 25,
    }, signal), [debounced, status, dateFrom, dateTo, page], view === 'list');
    const board = useApi((signal) => getVehicleServiceJobBoard({
        search: debounced || undefined,
        status: status || undefined,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
        pending_page: boardPages.pending,
        in_progress_page: boardPages.in_progress,
        completed_page: boardPages.completed,
        billing_closed_page: boardPages.billing_closed,
    }, signal), [debounced, status, dateFrom, dateTo, boardPages], view === 'board' && validDateRange);
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
    }, [debounced, dateFrom, dateTo], validDateRange, false);
    const tabsWithCounts = statusTabs.map((tab) => ({
        ...tab,
        label: renderStatusTabLabel(tab.label, tab.id, priorityCounts.data),
    }));
    const mobileStatusTabs = tabsWithCounts.filter((tab) => tab.id === '' || priorityStatuses.includes(tab.id as PriorityStatus));
    const activeFilterCount = [status, dateFrom, dateTo].filter(Boolean).length;
    const changeStatus = (nextStatus: StatusFilter) => {
        setStatus(nextStatus);
        setPage(1);
        setBoardPages(initialBoardPages);
        updateStatusSearchParam(nextStatus, setSearchParams, searchParams);
    };
    const changeView = (nextView: 'board' | 'list') => {
        const nextParams = new URLSearchParams(searchParams);
        if (nextView === 'board') nextParams.delete('view');
        else nextParams.set('view', 'list');
        setSearchParams(nextParams, { replace: true });
    };
    const changeSearch = (value: string) => {
        setSearch(value);
        setPage(1);
        setBoardPages(initialBoardPages);
    };
    const changeDate = (from: string, to: string) => {
        setDateFrom(from);
        setDateTo(to);
        setPage(1);
        setBoardPages(initialBoardPages);
    };
    const selectBoardDate = (preset: Exclude<BoardDatePreset, 'custom'>) => {
        const range = boardDateRange(preset);
        setBoardDatePreset(preset);
        changeDate(range.from, range.to);
    };
    const columns: DataColumn<VehicleServiceJob>[] = [
        { key: 'number', header: 'Job', render: (job) => <Link className="font-semibold text-blue-600 hover:text-blue-800 hover:underline" to={`/vehicle-service/jobs/${job.id}`}>{job.job_number}</Link> },
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
                            className="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-blue-600 transition hover:border-blue-200 hover:bg-blue-50"
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
                    <>
                        <div className="hidden flex-wrap gap-2 md:flex">
                            {canViewCommissions && <Button type="button" variant="secondary" onClick={() => setShowCommissionDefaults(true)}>Commission defaults</Button>}
                            {canCreate && <LinkButton to="/vehicle-service/jobs/create">New service job</LinkButton>}
                        </div>
                        <div className="flex w-full gap-2 md:hidden">
                            {canCreate && <LinkButton className="min-w-0 flex-1" to="/vehicle-service/jobs/create">New service job</LinkButton>}
                            {canViewCommissions && <ActionMenu label="More" className="shrink-0" triggerClassName="justify-center">
                                <Button className="w-full justify-start" type="button" variant="ghost" onClick={() => setShowCommissionDefaults(true)}>Commission defaults</Button>
                            </ActionMenu>}
                        </div>
                    </>
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
            <div className="mb-4 hidden rounded-xl border border-slate-200 bg-white md:block">
                <Tabs
                    id="vehicle-service-job-status-tabs"
                    tabs={tabsWithCounts}
                    active={status}
                    onChange={changeStatus}
                />
            </div>
            <div className="mb-4 flex justify-end">
                <div role="group" aria-label="Job view" className="inline-flex rounded-lg border border-slate-200 bg-white p-1">
                    <button type="button" aria-pressed={view === 'board'} onClick={() => changeView('board')} className={`rounded-md px-4 py-2 text-sm font-semibold ${view === 'board' ? 'bg-blue-100 text-blue-700' : 'text-slate-600 hover:bg-slate-50'}`}>Board</button>
                    <button type="button" aria-pressed={view === 'list'} onClick={() => changeView('list')} className={`rounded-md px-4 py-2 text-sm font-semibold ${view === 'list' ? 'bg-blue-100 text-blue-700' : 'text-slate-600 hover:bg-slate-50'}`}>List</button>
                </div>
            </div>
            {view === 'board' && <div className="mb-3 flex gap-2 overflow-x-auto pb-1 md:hidden" aria-label="Job status filters">
                {tabsWithCounts.map((tab) => <button key={tab.id} type="button" onClick={() => changeStatus(tab.id)} className={`min-h-10 shrink-0 rounded-full border px-3 text-sm font-medium ${status === tab.id ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-600'}`}>{tab.label}</button>)}
            </div>}
            {view === 'board' && <div className="mb-4 rounded-xl border border-slate-200 bg-white p-4">
                <div className="flex flex-wrap items-end gap-3">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="mr-1 text-sm font-semibold text-slate-600">Job date</span>
                        {([['today', 'Today'], ['week', 'This week'], ['last_week', 'Last week'], ['all', 'All dates']] as const).map(([key, label]) =>
                            <Button key={key} type="button" variant={boardDatePreset === key ? 'primary' : 'secondary'} onClick={() => selectBoardDate(key)}>{label}</Button>)}
                        <Button type="button" variant={boardDatePreset === 'custom' ? 'primary' : 'secondary'} onClick={() => setBoardDatePreset('custom')}>Custom range</Button>
                    </div>
                    <div className="min-w-56 flex-1"><Input label="Search" type="search" placeholder="Job, customer, or vehicle" value={search} onChange={(event) => changeSearch(event.target.value)} /></div>
                </div>
                {boardDatePreset === 'custom' && <div className="mt-4 grid gap-3 sm:grid-cols-2">
                    <Input label="From" type="date" value={dateFrom} max={dateTo || undefined} onChange={(event) => changeDate(event.target.value, dateTo)} />
                    <Input label="To" type="date" value={dateTo} min={dateFrom || undefined} onChange={(event) => changeDate(dateFrom, event.target.value)} />
                </div>}
                {!validDateRange && <p role="alert" className="mt-3 text-sm text-rose-700">The To date must be on or after the From date.</p>}
            </div>}
            {view === 'list' && <div className="mb-4 space-y-3 md:hidden">
                <div className="flex gap-2 overflow-x-auto pb-1">
                    {mobileStatusTabs.map((tab) => <button key={tab.id} type="button" onClick={() => changeStatus(tab.id)} className={`min-h-10 shrink-0 rounded-full border px-3 text-sm font-medium ${status === tab.id ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-600'}`}>{tab.label}</button>)}
                </div>
                <div className="grid grid-cols-[minmax(0,1fr)_auto] items-end gap-3">
                    <Input label="Search" type="search" placeholder="Job, customer, or vehicle" value={search} onChange={(event) => changeSearch(event.target.value)} />
                    <button type="button" className="inline-flex min-h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700" aria-expanded={filtersOpen} aria-controls="vehicle-service-job-filters" onClick={() => setFiltersOpen((open) => !open)}>Filters{activeFilterCount > 0 ? ` (${activeFilterCount})` : ''}</button>
                </div>
                <div id="vehicle-service-job-filters" className={`${filtersOpen ? 'grid' : 'hidden'} gap-3 sm:grid-cols-2`}>
                    <Select label="Status" value={status} options={[{ value: '', label: 'All statuses' }, ...statusOptions]} onChange={(event) => changeStatus(resolveStatusFilter(event.target.value))} />
                    <Input label="From" type="date" value={dateFrom} onChange={(event) => { setBoardDatePreset('custom'); changeDate(event.target.value, dateTo); }} />
                    <Input label="To" type="date" value={dateTo} onChange={(event) => { setBoardDatePreset('custom'); changeDate(dateFrom, event.target.value); }} />
                    {activeFilterCount > 0 && <button type="button" className="justify-self-start rounded-md px-2 py-1 text-sm font-semibold text-blue-700" onClick={() => { changeStatus(''); changeDate('', ''); setBoardDatePreset('all'); }}>Clear filters</button>}
                </div>
            </div>}
            {view === 'list' && <div className="mb-4 hidden gap-4 md:grid md:grid-cols-2 xl:grid-cols-4">
                <Input label="Search" type="search" placeholder="Job, customer, or vehicle" value={search} onChange={(event) => changeSearch(event.target.value)} />
                <Select label="Status" value={status} options={[{ value: '', label: 'all' }, ...statusOptions]} onChange={(event) => changeStatus(resolveStatusFilter(event.target.value))} />
                <Input label="From" type="date" value={dateFrom} onChange={(event) => { setBoardDatePreset('custom'); changeDate(event.target.value, dateTo); }} />
                <Input label="To" type="date" value={dateTo} onChange={(event) => { setBoardDatePreset('custom'); changeDate(dateFrom, event.target.value); }} />
            </div>}
            <ErrorAlert error={view === 'board' ? board.error : result.error} />
            {view === 'board' ? (board.loading ? <LoadingState label="Loading service job board..." /> : board.data ? <VehicleServiceJobBoard result={board.data} status={status} onLoadMore={(lane) => setBoardPages((current) => ({ ...current, [lane]: current[lane] + 1 }))} /> : null) : result.loading ? <LoadingState /> : <DataTable
                rows={result.data?.data ?? []}
                columns={columns}
                rowKey={(job) => job.id}
                mobileSummary={(job) => <Link className="break-words text-base font-semibold text-blue-600 hover:text-blue-800 hover:underline" to={`/vehicle-service/jobs/${job.id}`}>{job.job_number}</Link>}
                rowBadge={(job) => <VehicleServiceStatusBadge status={job.status} />}
                mobileDetails={(job) => <div className="space-y-3">
                    <div className="grid grid-cols-2 gap-3">
                        <div className="min-w-0"><p className="text-xs font-medium text-slate-500">Vehicle</p><p className="break-words font-medium text-slate-900">{readableRelation(job.vehicle)}</p></div>
                        <div className="min-w-0 text-right"><p className="text-xs font-medium text-slate-500">Total</p><p className="font-semibold text-slate-950"><MoneyDisplay value={job.grand_total} /></p></div>
                    </div>
                    <div><p className="text-xs font-medium text-slate-500">Customer</p><p className="break-words text-slate-800">{readableRelation(job.customer)}</p></div>
                    <p className="text-sm text-slate-600">{formatDate(job.job_date)}</p>
                </div>}
                mobileActions={(job) => canUpdate && editableStatuses.includes(job.status as typeof editableStatuses[number]) ? <Link className="inline-flex min-h-10 w-full items-center justify-center rounded-lg border border-slate-200 px-4 text-sm font-semibold text-blue-600 hover:bg-blue-50" to={`/vehicle-service/jobs/${job.id}/edit`}>Edit job</Link> : null}
            />}
            {view === 'list' && <Pagination meta={result.data?.meta} onPageChange={setPage} />}
        </>
    );
}

function resolveStatusFilter(value: string | null): StatusFilter {
    if (value === null || value === '') return '';
    return statuses.includes(value as (typeof statuses)[number]) ? value as StatusFilter : '';
}

function boardDateRange(preset: Exclude<BoardDatePreset, 'custom'>): { from: string; to: string } {
    if (preset === 'all') return { from: '', to: '' };

    const today = businessDateInputValue();
    if (preset === 'today') return { from: today, to: today };

    const date = new Date(`${today}T00:00:00Z`);
    const daysSinceMonday = (date.getUTCDay() + 6) % 7;
    date.setUTCDate(date.getUTCDate() - daysSinceMonday - (preset === 'last_week' ? 7 : 0));
    const from = date.toISOString().slice(0, 10);
    date.setUTCDate(date.getUTCDate() + 6);
    return { from, to: date.toISOString().slice(0, 10) };
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
