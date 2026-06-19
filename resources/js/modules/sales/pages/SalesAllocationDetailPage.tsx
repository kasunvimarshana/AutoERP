import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { useApi } from '@/shared/hooks/useApi';
import { formatDate } from '@/shared/utils/formatDate';
import { readableRelation } from '@/shared/utils/object';
import { getSalesAllocation, releaseSalesAllocation } from '../salesApi';
import { SalesStatusBadge } from '../components/SalesStatusBadge';
import type { SalesAllocationLine } from '../salesTypes';

export default function SalesAllocationDetailPage() {
    const id = Number(useParams().id);
    const navigate = useNavigate();
    const [busy, setBusy] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const result = useApi((signal) => getSalesAllocation(id, signal), [id]);

    if (result.loading) return <LoadingState />;
    if (result.error || !result.data) return <ErrorAlert error={result.error} />;

    const allocation = result.data;
    const columns: DataColumn<SalesAllocationLine>[] = [
        { key: 'line', header: '#', render: (row) => row.line_number ?? row.id },
        { key: 'item', header: 'Item', render: (row) => readableRelation(row.item) },
        { key: 'uom', header: 'UOM', render: (row) => readableRelation(row.uom) },
        { key: 'requested', header: 'Requested', render: (row) => row.requested_quantity },
        { key: 'allocated', header: 'Allocated', render: (row) => row.allocated_quantity },
        { key: 'issued', header: 'Issued', render: (row) => row.issued_quantity },
        { key: 'released', header: 'Released', render: (row) => row.released_quantity },
        { key: 'status', header: 'Status', render: (row) => <SalesStatusBadge status={row.status} /> },
    ];

    const release = async () => {
        if (busy) return;
        setBusy(true);
        setActionError(null);
        try {
            await releaseSalesAllocation(allocation.id);
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusy(false);
        }
    };

    return (
        <>
            <ContentHeader
                title={allocation.allocation_number ?? 'Sales allocation'}
                description={`Allocation date ${formatDate(allocation.allocation_date)}.`}
                actions={(
                    <div className="flex gap-2">
                        {allocation.status === 'active' && <Button type="button" variant="secondary" loading={busy} onClick={() => void release()}>Release</Button>}
                        <Button type="button" variant="ghost" onClick={() => navigate('/sales/allocations')}>Back</Button>
                    </div>
                )}
            />
            <ErrorAlert error={actionError} />
            <Panel title="Header">
                <dl className="grid gap-3 text-sm md:grid-cols-4">
                    <Row label="Status" value={<SalesStatusBadge status={allocation.status} />} />
                    <Row label="Sales order" value={readableRelation(allocation.sales_order)} />
                    <Row label="Customer" value={readableRelation(allocation.customer)} />
                    <Row label="Warehouse" value={readableRelation(allocation.warehouse)} />
                </dl>
            </Panel>
            <Panel title="Lines">
                <DataTable rows={allocation.lines ?? []} columns={columns} rowKey={(row) => row.id} />
            </Panel>
        </>
    );
}

function Row({ label, value }: { label: string; value: React.ReactNode }) {
    return <div><dt className="text-slate-500">{label}</dt><dd className="mt-1 font-semibold text-slate-900">{value}</dd></div>;
}
