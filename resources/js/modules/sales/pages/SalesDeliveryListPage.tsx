import { useState } from 'react';
import { Link } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { formatDate } from '@/shared/utils/formatDate';
import { readableRelation } from '@/shared/utils/object';
import { listSalesDeliveries, postSalesDelivery, reverseSalesDelivery } from '../salesApi';
import type { SalesDelivery } from '../salesTypes';

export default function SalesDeliveryListPage() {
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [page, setPage] = useState(1);
    const [busyId, setBusyId] = useState<number | null>(null);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const debounced = useDebounce(search);
    const result = useApi((signal) => listSalesDeliveries({
        search: debounced || undefined,
        status: status || undefined,
        page,
        per_page: 25,
    }, signal), [debounced, status, page]);

    const action = async (delivery: SalesDelivery, type: 'post' | 'reverse') => {
        setBusyId(delivery.id);
        setActionError(null);
        try {
            if (type === 'post') await postSalesDelivery(delivery.id);
            else await reverseSalesDelivery(delivery.id);
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusyId(null);
        }
    };

    const columns: DataColumn<SalesDelivery>[] = [
        { key: 'number', header: 'Delivery', render: (row) => row.delivery_number ?? 'Delivery' },
        { key: 'date', header: 'Date', render: (row) => formatDate(row.delivery_date) },
        { key: 'order', header: 'Sales order', render: (row) => readableRelation(row.sales_order) },
        { key: 'customer', header: 'Customer', render: (row) => readableRelation(row.customer) },
        { key: 'warehouse', header: 'Warehouse', render: (row) => readableRelation(row.warehouse) },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        {
            key: 'actions',
            header: 'Actions',
            render: (row) => <div className="flex gap-2">
                {row.status === 'draft' && <Button loading={busyId === row.id} onClick={() => action(row, 'post')}>Post</Button>}
                {row.status === 'posted' && <Button variant="danger" loading={busyId === row.id} onClick={() => action(row, 'reverse')}>Reverse</Button>}
            </div>,
        },
    ];

    return (
        <>
            <ContentHeader title="Sales deliveries" description="Dispatch approved order quantities and post Inventory issues for stockable items." actions={<Link to="/sales/deliveries/create"><Button>New delivery</Button></Link>} />
            <div className="mb-4 grid gap-4 md:grid-cols-2">
                <Input type="search" label="Search" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
                <Select label="Status" value={status} options={['draft', 'posted', 'partially_returned', 'returned', 'partially_invoiced', 'invoiced', 'reversed'].map((value) => ({ value, label: value.replaceAll('_', ' ') }))} onChange={(event) => { setStatus(event.target.value); setPage(1); }} />
            </div>
            <ErrorAlert error={actionError ?? result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} mobileSummary={(row) => row.delivery_number ?? 'Delivery'} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}
