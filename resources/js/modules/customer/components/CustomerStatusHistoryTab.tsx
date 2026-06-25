import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { useState } from 'react';
import { listCustomerStatusHistory } from '../customerApi';
import type { CustomerStatusHistory } from '../customerTypes';
import { CustomerRelationHeader } from './CustomerRelationHeader';
import { formatBusinessDateTime } from '@/shared/utils/businessDate';

export default function CustomerStatusHistoryTab({ customerId }: { customerId: number }) {
    const [page, setPage] = useState(1);
    const result = useApi((signal) => listCustomerStatusHistory(customerId, { page, per_page: 20 }, signal), [customerId, page]);
    const columns: DataColumn<CustomerStatusHistory>[] = [
        { key: 'from', header: 'From', render: (row) => row.old_status ? <StatusBadge status={row.old_status} /> : '-' },
        { key: 'to', header: 'To', render: (row) => <StatusBadge status={row.new_status} /> },
        { key: 'reason', header: 'Reason', render: (row) => row.reason ?? '-' },
        { key: 'changed', header: 'Changed at', render: (row) => formatBusinessDateTime(row.changed_at) },
    ];
    return <><CustomerRelationHeader title="Status history" description="Immutable customer status transition history." /><ErrorAlert error={result.error} />{result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}<Pagination meta={result.data?.meta} onPageChange={setPage} /></>;
}
