import { useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { Tabs } from '@/shared/components/Tabs';
import { useApi } from '@/shared/hooks/useApi';
import { formatDate } from '@/shared/utils/formatDate';
import { formatMoney } from '@/shared/utils/formatMoney';
import { approvePurchaseReturn, cancelPurchaseReturn, getPurchaseReturn, postPurchaseReturn, type PurchaseReturnLine } from '../purchaseApi';

type Tab = 'summary' | 'lines' | 'adjustments' | 'linked';

export default function PurchaseReturnDetailPage() {
    const id = Number(useParams().id);
    const result = useApi((signal) => getPurchaseReturn(id, signal), [id]);
    const [tab, setTab] = useState<Tab>('summary');
    const [actionError, setActionError] = useState<ApiError | null>(null);
    if (result.loading) return <LoadingState />;
    if (!result.data) return <ErrorAlert error={result.error} />;
    const row = result.data;
    const run = async (action: 'approve' | 'post' | 'cancel') => {
        setActionError(null);
        try {
            if (action === 'approve') result.setData(await approvePurchaseReturn(row.id));
            if (action === 'post') {
                await postPurchaseReturn(row.id);
                result.reload();
            }
            if (action === 'cancel') result.setData(await cancelPurchaseReturn(row.id));
        } catch (requestError) {
            setActionError(toApiError(requestError));
        }
    };
    const columns: DataColumn<PurchaseReturnLine>[] = [
        { key: 'item', header: 'Item', render: (line) => line.item?.name ?? '-' },
        { key: 'uom', header: 'UOM', render: (line) => line.uom?.code ?? '-' },
        { key: 'returned', header: 'Returned', render: (line) => line.returned_quantity },
        { key: 'source', header: 'Source qty', render: (line) => line.source_quantity ?? '-' },
        { key: 'remaining', header: 'Remaining', render: (line) => line.remaining_quantity ?? '-' },
        { key: 'price', header: 'Unit price', render: (line) => formatMoney(line.unit_price) },
        { key: 'total', header: 'Total', render: (line) => formatMoney(line.line_total) },
    ];
    return (
        <div className="space-y-5">
            <ContentHeader
                title={row.return_number ?? `Return #${row.id}`}
                description={formatDate(row.return_date)}
                actions={<>{row.status === 'draft' && <Button onClick={() => run('approve')}>Approve</Button>}{(row.status === 'draft' || row.status === 'approved') && <Button variant="secondary" onClick={() => run('post')}>Post</Button>}{row.status !== 'posted' && row.status !== 'cancelled' && <Button variant="ghost" onClick={() => run('cancel')}>Cancel</Button>}</>}
            />
            <ErrorAlert error={result.error ?? actionError} />
            <Panel>
                <Tabs tabs={[{ id: 'summary', label: 'Summary' }, { id: 'lines', label: 'Lines' }, { id: 'adjustments', label: 'Adjustment allocations' }, { id: 'linked', label: 'Linked documents' }]} active={tab} onChange={setTab} />
                <div className="p-5">
                    {tab === 'summary' && <DetailGrid items={[
                        { label: 'Status', value: <StatusBadge status={row.status} /> },
                        { label: 'Type', value: row.return_type?.replaceAll('_', ' ') ?? '-' },
                        { label: 'Supplier', value: row.supplier?.name ?? '-' },
                        { label: 'Warehouse', value: row.warehouse?.name ?? '-' },
                        { label: 'Affects payable', value: row.affects_supplier_balance ? 'Yes' : 'No' },
                        { label: 'Grand total', value: formatMoney(row.grand_total) },
                        { label: 'Reason', value: row.reason ?? '-' },
                    ]} />}
                    {tab === 'lines' && <DataTable rows={row.lines ?? []} columns={columns} rowKey={(line) => line.id ?? `${line.source_line_type}-${line.source_line_id}`} />}
                    {tab === 'adjustments' && <DataTable rows={row.adjustment_allocations ?? []} columns={[
                        { key: 'type', header: 'Type', render: (allocation) => String(allocation.adjustment_type ?? '-') },
                        { key: 'effect', header: 'Effect', render: (allocation) => String(allocation.effect ?? '-') },
                        { key: 'returned', header: 'Returned', render: (allocation) => formatMoney(String(allocation.returned_amount ?? '0.000000')) },
                        { key: 'remaining', header: 'Remaining', render: (allocation) => formatMoney(String(allocation.remaining_amount ?? '0.000000')) },
                    ]} rowKey={(allocation) => String(allocation.id ?? allocation.adjustment_type)} />}
                    {tab === 'linked' && <DetailGrid items={[
                        { label: 'Source', value: row.source?.id ? `${row.source.type} #${row.source.id}` : '-' },
                        { label: 'Debit note', value: row.debit_note_id ? <Link className="text-sky-700 hover:underline" to={`/purchase/debit-notes/${row.debit_note_id}`}>Debit note #{row.debit_note_id}</Link> : '-' },
                    ]} />}
                </div>
            </Panel>
        </div>
    );
}
