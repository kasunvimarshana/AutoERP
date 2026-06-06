import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { useState } from 'react';
import { listSupplierStatusHistory } from '../supplierApi';
import type { SupplierStatusHistory } from '../supplierTypes';
import { SupplierRelationHeader } from './SupplierRelationHeader';

export default function SupplierStatusHistoryTab({ supplierId }: { supplierId: number }) {
    const [page, setPage] = useState(1);
    const result = useApi((signal) => listSupplierStatusHistory(supplierId, { page, per_page: 20 }, signal), [supplierId, page]);
    const columns: DataColumn<SupplierStatusHistory>[] = [
        { key: 'from', header: 'From', render: (row) => row.old_status ? <StatusBadge status={row.old_status} /> : '-' },
        { key: 'to', header: 'To', render: (row) => <StatusBadge status={row.new_status} /> },
        { key: 'reason', header: 'Reason', render: (row) => row.reason ?? '-' },
        { key: 'changed', header: 'Changed at', render: (row) => new Date(row.changed_at).toLocaleString() },
    ];
    return <><SupplierRelationHeader title="Status history" description="Immutable supplier status transition history." /><ErrorAlert error={result.error} />{result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}<Pagination meta={result.data?.meta} onPageChange={setPage} /></>;
}
