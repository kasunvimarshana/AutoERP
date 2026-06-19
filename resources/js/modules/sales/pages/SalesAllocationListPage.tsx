import { useState } from 'react';
import { Link } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { formatDate } from '@/shared/utils/formatDate';
import { readableRelation } from '@/shared/utils/object';
import { listSalesAllocations, releaseSalesAllocation } from '../salesApi';
import { SalesStatusBadge } from '../components/SalesStatusBadge';
import type { SalesAllocation } from '../salesTypes';

const statuses = ['active', 'partially_released', 'released', 'issued', 'cancelled'];

export default function SalesAllocationListPage() {
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [page, setPage] = useState(1);
    const [busyId, setBusyId] = useState<number | null>(null);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const debounced = useDebounce(search);
    const result = useApi((signal) => listSalesAllocations({
        search: debounced || undefined,
        status: status || undefined,
        page,
        per_page: 25,
    }, signal), [debounced, page, status]);

    const release = async (allocation: SalesAllocation) => {
        setBusyId(allocation.id);
        setActionError(null);
        try {
            await releaseSalesAllocation(allocation.id);
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusyId(null);
        }
    };

    const columns: DataColumn<SalesAllocation>[] = [
        {
            key: 'number',
            header: 'Allocation',
            render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/sales/allocations/${row.id}`}>{row.allocation_number ?? 'Allocation'}</Link>,
        },
        { key: 'date', header: 'Date', render: (row) => formatDate(row.allocation_date) },
        { key: 'order', header: 'Sales order', render: (row) => readableRelation(row.sales_order) },
        { key: 'customer', header: 'Customer', render: (row) => readableRelation(row.customer) },
        { key: 'warehouse', header: 'Warehouse', render: (row) => readableRelation(row.warehouse) },
        { key: 'status', header: 'Status', render: (row) => <SalesStatusBadge status={row.status} /> },
        {
            key: 'actions',
            header: 'Actions',
            render: (row) => (
                <div className="flex gap-2">
                    <LinkButton to={`/sales/allocations/${row.id}`} variant="ghost">View</LinkButton>
                    {row.status === 'active' && <Button type="button" variant="secondary" loading={busyId === row.id} onClick={() => void release(row)}>Release</Button>}
                </div>
            ),
        },
    ];

    return (
        <>
            <ContentHeader title="Sales allocations" description="Reserve stock for approved sales orders before delivery." actions={<LinkButton to="/sales/allocations/create">New allocation</LinkButton>} />
            <div className="mb-4 grid gap-4 md:grid-cols-2">
                <Input type="search" label="Search" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
                <Select label="Status" value={status} options={statuses.map((value) => ({ value, label: value.replaceAll('_', ' ') }))} onChange={(event) => { setStatus(event.target.value); setPage(1); }} />
            </div>
            <ErrorAlert error={actionError ?? result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} mobileSummary={(row) => row.allocation_number ?? 'Allocation'} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}
