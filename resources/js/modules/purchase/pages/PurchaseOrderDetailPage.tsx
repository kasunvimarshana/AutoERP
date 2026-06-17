import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { approvePurchaseOrder, cancelPurchaseOrder, closePurchaseOrder, deletePurchaseOrder, getPurchaseOrder, submitPurchaseOrder } from '../purchaseApi';
import { useAuth } from '@/modules/auth/AuthProvider';
import { useApi } from '@/shared/hooks/useApi';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Panel } from '@/shared/components/Panel';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { QuantityDisplay } from '@/shared/components/QuantityDisplay';
import { LoadingState } from '@/shared/components/LoadingState';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LinkButton } from '@/shared/components/Button';
import { EntityDetailLayout } from '@/shared/components/EntityDetailLayout';
import { formatDate } from '@/shared/utils/formatDate';
import { readableRelation } from '@/shared/utils/object';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { PurchaseOrderActions } from '../components/PurchaseOrderActions';
import { PurchaseOrderStatusBadge } from '../components/PurchaseOrderStatusBadge';
import { PurchaseOrderTabs } from '../components/PurchaseOrderTabs';
import { purchaseOrderCapabilities } from '../purchaseCapabilities';
import { hasPurchasePermission, purchasePermissions } from '../purchasePermissions';

export default function PurchaseOrderDetailPage() {
    const id = Number(useParams().id);
    const navigate = useNavigate();
    const auth = useAuth();
    const result = useApi((signal) => getPurchaseOrder(id, signal), [id]);
    const [busy, setBusy] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);

    const run = async (action: 'submit' | 'approve' | 'cancel' | 'close' | 'delete') => {
        if (!result.data) return;
        setBusy(true);
        setActionError(null);
        try {
            if (action === 'submit') result.setData(await submitPurchaseOrder(result.data.id));
            if (action === 'approve') result.setData(await approvePurchaseOrder(result.data.id));
            if (action === 'cancel') result.setData(await cancelPurchaseOrder(result.data.id));
            if (action === 'close') result.setData(await closePurchaseOrder(result.data.id));
            if (action === 'delete') {
                await deletePurchaseOrder(result.data.id);
                navigate('/purchase/orders');
            }
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusy(false);
        }
    };

    if (result.loading) return <LoadingState />;
    if (!result.data) return <ErrorAlert error={result.error} />;
    const order = result.data;
    const capabilities = purchaseOrderCapabilities(order);
    const summary = (
        <DetailGrid items={[
            { label: 'Workflow', value: <PurchaseOrderStatusBadge status={order.workflow_status ?? order.status} /> },
            { label: 'Receipt', value: order.receipt_status?.replaceAll('_', ' ') ?? '-' },
            { label: 'Invoice', value: order.invoice_status?.replaceAll('_', ' ') ?? '-' },
            { label: 'Return', value: order.return_status?.replaceAll('_', ' ') ?? '-' },
            { label: 'Supplier', value: readableRelation(order.supplier) },
            { label: 'Warehouse', value: readableRelation(order.warehouse) },
            { label: 'Location', value: readableRelation(order.warehouse_location) },
            { label: 'Order date', value: formatDate(order.purchase_order_date) },
            { label: 'Expected delivery', value: formatDate(order.expected_delivery_date) },
            { label: 'Subtotal', value: <MoneyDisplay value={order.subtotal} currency={order.currency?.code ?? undefined} /> },
            { label: 'Line discounts', value: <MoneyDisplay value={order.discount_total} currency={order.currency?.code ?? undefined} /> },
            { label: 'Line tax', value: <MoneyDisplay value={order.tax_total} currency={order.currency?.code ?? undefined} /> },
            { label: 'Line charges', value: <MoneyDisplay value={order.charge_total} currency={order.currency?.code ?? undefined} /> },
            { label: 'Header adjustment net', value: <MoneyDisplay value={order.adjustment_total} currency={order.currency?.code ?? undefined} /> },
            { label: 'Grand total', value: <MoneyDisplay value={order.grand_total} currency={order.currency?.code ?? undefined} /> },
            { label: 'Received quantity', value: <QuantityDisplay value={order.received_quantity} precision={6} /> },
            { label: 'Invoiced quantity', value: <QuantityDisplay value={order.invoiced_quantity} precision={6} /> },
            { label: 'Returned quantity', value: <QuantityDisplay value={order.returned_quantity} precision={6} /> },
            { label: 'Approved at', value: formatDate(order.approved_at) },
            { label: 'Closed at', value: formatDate(order.closed_at) },
            { label: 'Notes', value: order.notes ?? '-' },
        ]} />
    );
    const sideSummary = (
        <Panel className="rounded-lg">
            <div className="space-y-4">
                <div>
                    <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</p>
                    <div className="mt-2"><PurchaseOrderStatusBadge status={order.workflow_status ?? order.status} /></div>
                </div>
                <DetailGrid items={[
                    { label: 'Supplier', value: readableRelation(order.supplier) },
                    { label: 'Warehouse', value: readableRelation(order.warehouse) },
                    { label: 'Expected', value: formatDate(order.expected_delivery_date) },
                    { label: 'Grand total', value: <MoneyDisplay value={order.grand_total} currency={order.currency?.code ?? undefined} /> },
                ]} />
            </div>
        </Panel>
    );

    return (
        <>
            <ContentHeader
                title={order.purchase_order_number ?? 'Purchase order'}
                description={formatDate(order.purchase_order_date)}
                actions={<PurchaseOrderActions
                    order={order}
                    busy={busy}
                    canUpdate={hasPurchasePermission(auth.permissions, purchasePermissions.ordersUpdate)}
                    onSubmit={hasPurchasePermission(auth.permissions, purchasePermissions.ordersSubmit) ? () => run('submit') : undefined}
                    onApprove={hasPurchasePermission(auth.permissions, purchasePermissions.ordersApprove) ? () => run('approve') : undefined}
                    onCancel={hasPurchasePermission(auth.permissions, purchasePermissions.ordersCancel) ? () => run('cancel') : undefined}
                    onClose={hasPurchasePermission(auth.permissions, purchasePermissions.ordersClose) ? () => run('close') : undefined}
                    onDelete={hasPurchasePermission(auth.permissions, purchasePermissions.ordersDelete) ? () => run('delete') : undefined}
                />}
            />
            <ErrorAlert error={actionError ?? result.error} />
            <EntityDetailLayout
                summary={sideSummary}
                actions={
                    <>
                        {capabilities.canReceive && hasPurchasePermission(auth.permissions, purchasePermissions.goodsReceiptsCreate) && <LinkButton to={`/purchase/goods-receipts/create?purchase_order_id=${order.id}`} variant="secondary" className="w-full">Receive goods</LinkButton>}
                        {capabilities.canInvoice && hasPurchasePermission(auth.permissions, purchasePermissions.supplierInvoicesCreate) && <LinkButton to={`/purchase/invoices/create?purchase_order_id=${order.id}`} variant="secondary" className="w-full">Create supplier invoice</LinkButton>}
                        {hasPurchasePermission(auth.permissions, purchasePermissions.paymentsExecute) && <LinkButton to={`/purchase/payments/prepare?purchase_order_id=${order.id}`} variant="secondary" className="w-full">Prepare payment</LinkButton>}
                        {capabilities.canReturn && hasPurchasePermission(auth.permissions, purchasePermissions.returnsCreate) && <LinkButton to={`/purchase/returns/create?purchase_order_id=${order.id}`} variant="secondary" className="w-full">Create return</LinkButton>}
                    </>
                }
            >
                <Panel className="p-0">
                    <PurchaseOrderTabs order={order} summary={summary} />
                </Panel>
            </EntityDetailLayout>
        </>
    );
}
