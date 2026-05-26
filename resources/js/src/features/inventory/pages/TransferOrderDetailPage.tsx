import { useParams } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { PageHeader } from '../../../components/layout/PageHeader';
import { DataTable, type DataTableColumn } from '../../../components/tables';
import { useTenant } from '../../auth/context/TenantContext';
import { ConfirmWorkflowModal, DocumentHeader, WorkflowActionBar } from '../../shared/workflow';
import { formatDate, formatQuantity, parsePositiveInteger } from '../../shared/utils';
import { useApproveTransferOrder, useReceiveTransferOrder, useTransferOrder } from '../hooks';
import type { TransferOrderLineRecord } from '../types';
import { useState } from 'react';

function buildReceivePayload(tenantId: number, lines: TransferOrderLineRecord[]) {
    return {
        tenant_id: tenantId,
        lines: lines.map((line) => ({
            line_id: line.id,
            received_qty: Math.max(Number(line.requested_qty) - Number(line.received_qty ?? 0), 0) || Number(line.requested_qty),
        })),
    };
}

export function TransferOrderDetailPage() {
    const { tenantId } = useTenant();
    const { transferOrderId: transferOrderIdParam } = useParams();
    const transferOrderId = parsePositiveInteger(transferOrderIdParam ?? null, 0);
    const [action, setAction] = useState<'approve' | 'receive' | null>(null);
    const transferOrderQuery = useTransferOrder(transferOrderId, tenantId, transferOrderId > 0);
    const approveMutation = useApproveTransferOrder(transferOrderId, tenantId);
    const receiveMutation = useReceiveTransferOrder(transferOrderId, tenantId);

    if (transferOrderId <= 0) {
        return <ErrorState description="The transfer order route is missing a valid transfer order ID." title="Invalid transfer order route" />;
    }

    if (transferOrderQuery.isPending) {
        return <LoadingState lines={10} />;
    }

    if (transferOrderQuery.isError) {
        return <ErrorState description={transferOrderQuery.error.message} title="Unable to load transfer order" />;
    }

    const transferOrder = transferOrderQuery.data;
    const columns: DataTableColumn<TransferOrderLineRecord>[] = [
        { key: 'product_id', header: 'Product', render: (line) => <span className="font-medium text-stone-950">#{line.product_id}</span> },
        { key: 'uom_id', header: 'UOM', render: (line) => <span className="text-sm text-stone-700">#{line.uom_id}</span> },
        { key: 'requested_qty', header: 'Requested', render: (line) => <span className="text-sm text-stone-700">{formatQuantity(line.requested_qty)}</span> },
        { key: 'shipped_qty', header: 'Shipped', render: (line) => <span className="text-sm text-stone-700">{formatQuantity(line.shipped_qty)}</span> },
        { key: 'received_qty', header: 'Received', render: (line) => <span className="text-sm text-stone-700">{formatQuantity(line.received_qty)}</span> },
    ];

    async function handleActionConfirm() {
        if (action === 'approve') {
            await approveMutation.mutateAsync();
        } else if (action === 'receive') {
            await receiveMutation.mutateAsync(buildReceivePayload(tenantId, transferOrder.lines));
        }

        setAction(null);
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader breadcrumbs={[{ label: 'Inventory', href: '/inventory' }, { label: 'Transfer Orders', href: '/inventory/transfer-orders' }, { label: transferOrder.transfer_number }]} description="Transfer detail keeps route, quantities, and workflow actions together so approval and receipt decisions stay visible." title={transferOrder.transfer_number} />

            <DocumentHeader
                dateLabel="Request Date"
                dateValue={transferOrder.request_date}
                documentNumber={transferOrder.transfer_number}
                documentNumberLabel="Transfer Order"
                helperText="Use the action bar for approval or receipt. Receive currently uses the remaining quantity on each line from the backend detail response."
                metrics={[
                    { label: 'Source Warehouse', value: `#${transferOrder.from_warehouse_id}` },
                    { label: 'Destination Warehouse', value: `#${transferOrder.to_warehouse_id}` },
                    { label: 'Expected Date', value: formatDate(transferOrder.expected_date) },
                ]}
                primaryPartyLabel="Route"
                primaryPartyValue={`Warehouse #${transferOrder.from_warehouse_id} -> Warehouse #${transferOrder.to_warehouse_id}`}
                status={transferOrder.status}
                title="Transfer workflow"
            />

            <ContentCard className="p-0">
                <DataTable columns={columns} emptyState={<div className="p-6 text-sm text-stone-500">No transfer lines available.</div>} getRowKey={(line) => line.id} rows={transferOrder.lines} />
            </ContentCard>

            <ContentCard>
                <WorkflowActionBar description="Approve while the order is in draft, then receive using the remaining line quantities once the document is approved or in transit.">
                    {transferOrder.status === 'draft' ? <Button onClick={() => setAction('approve')} type="button">Approve</Button> : null}
                    {(transferOrder.status === 'approved' || transferOrder.status === 'in_transit') ? <Button onClick={() => setAction('receive')} type="button">Receive</Button> : null}
                </WorkflowActionBar>
            </ContentCard>

            <ConfirmWorkflowModal
                confirmLabel={action === 'approve' ? 'Approve transfer order' : 'Receive transfer order'}
                description={action ? `${action === 'approve' ? 'Approve' : 'Receive'} ${transferOrder.transfer_number}?` : ''}
                isLoading={approveMutation.isPending || receiveMutation.isPending}
                onCancel={() => setAction(null)}
                onConfirm={() => void handleActionConfirm()}
                open={Boolean(action)}
                title={action === 'approve' ? 'Approve transfer order' : 'Receive transfer order'}
            />
        </div>
    );
}
