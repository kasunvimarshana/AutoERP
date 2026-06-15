import { useState } from 'react';

import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { formatDate } from '@/shared/utils/formatDate';
import { readableRelation } from '@/shared/utils/object';
import { approveSalesReturn, cancelSalesReturn, listSalesReturns, postSalesReturn } from '../salesApi';
import type { SalesReturn } from '../salesTypes';

export default function SalesReturnListPage() {
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [page, setPage] = useState(1);
    const [busyId, setBusyId] = useState<number | null>(null);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const debounced = useDebounce(search);
    const result = useApi((signal) => listSalesReturns({ search: debounced || undefined, status: status || undefined, page, per_page: 25 }, signal), [debounced, status, page]);

    const action = async (row: SalesReturn, type: 'approve' | 'post' | 'cancel') => {
        setBusyId(row.id);
        setActionError(null);
        try {
            if (type === 'approve') await approveSalesReturn(row.id);
            if (type === 'post') await postSalesReturn(row.id);
            if (type === 'cancel') await cancelSalesReturn(row.id);
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusyId(null);
        }
    };

    const columns: DataColumn<SalesReturn>[] = [
        { key: 'number', header: 'Return', render: (row) => row.return_number ?? 'Sales return' },
        { key: 'date', header: 'Date', render: (row) => formatDate(row.return_date) },
        { key: 'customer', header: 'Customer', render: (row) => readableRelation(row.customer) },
        { key: 'type', header: 'Scenario', render: (row) => row.return_type?.replaceAll('_', ' ') ?? '-' },
        { key: 'total', header: 'Total', render: (row) => <MoneyDisplay value={row.grand_total} /> },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        {
            key: 'actions',
            header: 'Actions',
            render: (row) => <div className="flex flex-wrap gap-2">
                {row.status === 'draft' && row.approval_required && <Button loading={busyId === row.id} onClick={() => action(row, 'approve')}>Approve</Button>}
                {(row.status === 'draft' && !row.approval_required || row.status === 'approved') && <Button loading={busyId === row.id} onClick={() => action(row, 'post')}>Post</Button>}
                {['draft', 'approved'].includes(row.status ?? '') && <Button variant="danger" loading={busyId === row.id} onClick={() => action(row, 'cancel')}>Cancel</Button>}
            </div>,
        },
    ];

    return (
        <>
            <ContentHeader title="Sales returns" description="Referenced, manual, credit-only, inventory-only, warranty, exchange, damaged, and imported return workflows." actions={<LinkButton to="/sales/returns/create">New return</LinkButton>} />
            <div className="mb-4 grid gap-4 md:grid-cols-2">
                <Input type="search" label="Search" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
                <Select label="Status" value={status} options={['draft', 'approved', 'posted', 'cancelled', 'reversed'].map((value) => ({ value, label: value }))} onChange={(event) => { setStatus(event.target.value); setPage(1); }} />
            </div>
            <ErrorAlert error={actionError ?? result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} mobileSummary={(row) => row.return_number ?? 'Sales return'} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}
