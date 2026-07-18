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
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import { notifySuccess } from '@/shared/notifications/appToast';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { formatDate } from '@/shared/utils/formatDate';
import { readableRelation } from '@/shared/utils/object';
import { listVehicleServiceJobs } from '../vehicleServiceApi';
import { vehicleServicePermissions } from '../vehicleServicePermissions';
import type { VehicleServiceJob } from '../vehicleServiceTypes';
import VehicleServiceCommissionSettingsPanel from './VehicleServiceCommissionSettingsPanel';
import { VehicleServiceStatusBadge } from '../components/VehicleServiceStatusBadge';

const editableStatuses = ['draft', 'inspected', 'in_progress'] as const;
const statuses = ['draft', 'inspected', 'in_progress', 'completed', 'invoiced', 'partially_paid', 'paid', 'cancelled']
    .map((value) => ({ value, label: value.replaceAll('_', ' ') }));

export default function VehicleServiceJobListPage() {
    const auth = useAuth();
    const canCreate = hasPermission(auth, vehicleServicePermissions.jobsCreate);
    const canUpdate = hasPermission(auth, vehicleServicePermissions.jobsUpdate);
    const canViewCommissions = hasPermission(auth, vehicleServicePermissions.commissionsView);
    const [searchParams] = useSearchParams();
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState(searchParams.get('status') ?? '');
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
    const columns: DataColumn<VehicleServiceJob>[] = [
        { key: 'number', header: 'Job', render: (job) => <Link className="font-semibold text-sky-700 hover:underline" to={`/vehicle-service/jobs/${job.id}`}>{job.job_number}</Link> },
        { key: 'date', header: 'Date', render: (job) => formatDate(job.job_date) },
        { key: 'customer', header: 'Customer', render: (job) => readableRelation(job.customer) },
        { key: 'vehicle', header: 'Vehicle', render: (job) => readableRelation(job.vehicle) },
        { key: 'total', header: 'Total', render: (job) => <MoneyDisplay value={job.grand_total} /> },
        { key: 'status', header: 'Status', render: (job) => <VehicleServiceStatusBadge status={job.status} /> },
        { key: 'actions', header: '', render: (job) => <div className="flex gap-2"><LinkButton to={`/vehicle-service/jobs/${job.id}`} variant="ghost">View</LinkButton>{canUpdate && editableStatuses.includes(job.status as typeof editableStatuses[number]) && <LinkButton to={`/vehicle-service/jobs/${job.id}/edit`} variant="secondary">Edit</LinkButton>}</div> },
    ];

    return (
        <>
            <ContentHeader
                title="Vehicle service jobs"
                description="Service workflow, mixed job lines, workforce, stock, invoicing, and payment preparation."
                actions={(
                    <div className="flex flex-wrap gap-2">
                        {canViewCommissions && (
                            <Button type="button" variant="secondary" onClick={() => setShowCommissionDefaults((current) => !current)}>
                                {showCommissionDefaults ? 'Hide commission defaults' : 'Commission defaults'}
                            </Button>
                        )}
                        {canCreate && <LinkButton to="/vehicle-service/jobs/create">New service job</LinkButton>}
                    </div>
                )}
            />
            {canViewCommissions && showCommissionDefaults && (
                <VehicleServiceCommissionSettingsPanel
                    onSaved={() => {
                        notifySuccess('Commission defaults updated for new vehicle service jobs.', 'Commission defaults saved');
                        setShowCommissionDefaults(false);
                    }}
                />
            )}
            <div className="mb-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Input label="Search" type="search" placeholder="Job, customer, or vehicle" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
                <Select label="Status" value={status} options={statuses} onChange={(event) => { setStatus(event.target.value); setPage(1); }} />
                <Input label="From" type="date" value={dateFrom} onChange={(event) => { setDateFrom(event.target.value); setPage(1); }} />
                <Input label="To" type="date" value={dateTo} onChange={(event) => { setDateTo(event.target.value); setPage(1); }} />
            </div>
            <ErrorAlert error={result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(job) => job.id} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}
