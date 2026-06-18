import { useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
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
import { getGoodsReceipt, postGoodsReceipt, reverseGoodsReceipt, type GoodsReceiptLine } from '../purchaseApi';

type Tab = 'summary' | 'lines' | 'adjustments' | 'linked';

export default function GoodsReceiptDetailPage() {
    const id = Number(useParams().id);
    const result = useApi((signal) => getGoodsReceipt(id, signal), [id]);
    const [tab, setTab] = useState<Tab>('summary');
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [busy, setBusy] = useState(false);

    if (result.loading) return <LoadingState />;
    if (!result.data) return <ErrorAlert error={result.error} />;

    const grn = result.data;
    const run = async (action: 'post' | 'reverse') => {
        if (!window.confirm(`Confirm ${action} for this goods receipt?`)) return;
        setBusy(true);
        setActionError(null);
        try {
            result.setData(action === 'post' ? await postGoodsReceipt(grn.id) : await reverseGoodsReceipt(grn.id));
        } catch (requestError) {
            setActionError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    };
    const columns: DataColumn<GoodsReceiptLine>[] = [
        { key: 'item', header: 'Item', render: (row) => row.item?.name ?? '-' },
        { key: 'uom', header: 'UOM', render: (row) => row.uom?.code ?? '-' },
        { key: 'received', header: 'Received', render: (row) => row.received_quantity },
        { key: 'accepted', header: 'Accepted', render: (row) => row.accepted_quantity },
        { key: 'invoiced', header: 'Invoiced', render: (row) => row.invoiced_quantity ?? '0.000000' },
        { key: 'returned', header: 'Returned', render: (row) => row.returned_quantity ?? '0.000000' },
        { key: 'total', header: 'Total', render: (row) => formatMoney(row.line_total) },
    ];

    return (
        <div className="space-y-5">
            <ContentHeader
                title={grn.grn_number ?? 'Goods receipt'}
                description={formatDate(grn.received_date)}
                actions={<div className="flex flex-wrap justify-end gap-2">
                    {grn.status === 'posted' && <LinkButton to={`/purchase/invoices/create?goods_receipt_id=${grn.id}`} variant="secondary">Create invoice</LinkButton>}
                    {grn.status === 'posted' && <LinkButton to={`/purchase/returns/create?goods_receipt_id=${grn.id}`} variant="secondary">Create return</LinkButton>}
                    {grn.status === 'draft' && <Button loading={busy} onClick={() => void run('post')}>Post</Button>}
                    {grn.status === 'posted' && <Button loading={busy} variant="secondary" onClick={() => void run('reverse')}>Reverse</Button>}
                </div>}
            />
            <ErrorAlert error={result.error ?? actionError} />
            <Panel>
                <Tabs tabs={[{ id: 'summary', label: 'Summary' }, { id: 'lines', label: 'Lines' }, { id: 'adjustments', label: 'Adjustments' }, { id: 'linked', label: 'Linked documents' }]} active={tab} onChange={setTab} />
                <div className="p-5">
                    {tab === 'summary' && <DetailGrid items={[
                        { label: 'Status', value: <StatusBadge status={grn.status} /> },
                        { label: 'Supplier', value: grn.supplier?.name ?? '-' },
                        { label: 'Purchase order', value: grn.purchase_order?.purchase_order_number ?? grn.purchase_order?.name ?? '-' },
                        { label: 'Warehouse', value: grn.warehouse?.name ?? '-' },
                        { label: 'Subtotal', value: formatMoney(grn.subtotal) },
                        { label: 'Grand total', value: formatMoney(grn.grand_total) },
                        { label: 'Posted at', value: formatDate(grn.posted_at) },
                    ]} />}
                    {tab === 'lines' && <DataTable rows={grn.lines ?? []} columns={columns} rowKey={(row) => row.id ?? row.purchase_order_line_id ?? `${row.item?.id}-${row.received_quantity}`} />}
                    {tab === 'adjustments' && <DataTable rows={grn.adjustments ?? []} columns={[
                        { key: 'name', header: 'Name', render: (row) => row.name },
                        { key: 'type', header: 'Type', render: (row) => row.adjustment_type },
                        { key: 'effect', header: 'Effect', render: (row) => row.effect },
                        { key: 'amount', header: 'Amount', render: (row) => formatMoney(row.amount) },
                    ]} rowKey={(row) => row.id ?? row.name} />}
                    {tab === 'linked' && <DetailGrid items={[
                        { label: 'Purchase order', value: grn.purchase_order?.id ? <Link className="text-sky-700 hover:underline" to={`/purchase/orders/${grn.purchase_order.id}`}>{grn.purchase_order.purchase_order_number ?? grn.purchase_order.name}</Link> : '-' },
                        { label: 'Returns', value: <Link className="text-sky-700 hover:underline" to={`/purchase/returns/create?goods_receipt_id=${grn.id}`}>Create referenced return</Link> },
                        { label: 'Invoices', value: <Link className="text-sky-700 hover:underline" to={`/purchase/invoices/create?goods_receipt_id=${grn.id}`}>Create supplier invoice</Link> },
                    ]} />}
                </div>
            </Panel>
        </div>
    );
}
