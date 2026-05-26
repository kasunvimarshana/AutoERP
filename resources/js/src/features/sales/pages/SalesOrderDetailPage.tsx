import { useState } from 'react';
import { useParams } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { PageHeader } from '../../../components/layout/PageHeader';
import { ConfirmWorkflowModal, DocumentHeader, DocumentLineItemsTable, RelatedDocumentsTabs, TimelinePlaceholder, TotalsSummaryCard, WorkflowActionBar } from '../../shared/workflow';
import { formatDate, parsePositiveInteger } from '../../shared/utils';
import { useCancelSalesOrder, useConfirmSalesOrder, useSalesOrder } from '../hooks';

export function SalesOrderDetailPage() {
    const { salesOrderId: salesOrderIdParam } = useParams();
    const salesOrderId = parsePositiveInteger(salesOrderIdParam ?? null, 0);
    const [tab, setTab] = useState('overview');
    const [action, setAction] = useState<'confirm' | 'cancel' | null>(null);
    const salesOrderQuery = useSalesOrder(salesOrderId, salesOrderId > 0);
    const confirmMutation = useConfirmSalesOrder(salesOrderId);
    const cancelMutation = useCancelSalesOrder(salesOrderId);

    if (salesOrderId <= 0) {
        return <ErrorState description="The sales order route is missing a valid sales order ID." title="Invalid sales order route" />;
    }

    if (salesOrderQuery.isPending) {
        return <LoadingState lines={10} />;
    }

    if (salesOrderQuery.isError) {
        return <ErrorState description={salesOrderQuery.error.message} title="Unable to load sales order" />;
    }

    const salesOrder = salesOrderQuery.data;

    async function handleActionConfirm() {
        if (action === 'confirm') {
            await confirmMutation.mutateAsync();
        } else if (action === 'cancel') {
            await cancelMutation.mutateAsync();
        }

        setAction(null);
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader breadcrumbs={[{ label: 'Sales', href: '/sales/orders' }, { label: 'Sales Orders', href: '/sales/orders' }, { label: salesOrder.so_number }]} description="Sales order detail keeps customer, warehouse, totals, and workflow actions together in one operational document view." title={salesOrder.so_number} />

            <DocumentHeader
                dateLabel="Order Date"
                dateValue={salesOrder.order_date}
                documentNumber={salesOrder.so_number}
                documentNumberLabel="Sales Order"
                helperText="The current sales order show resource returns the document header without line arrays, so this screen keeps the workflow and totals visible while reserving the line table area for future backend expansion."
                metrics={[
                    { label: 'Customer', value: `#${salesOrder.customer_id}` },
                    { label: 'Warehouse', value: `#${salesOrder.warehouse_id}` },
                    { label: 'Requested Delivery', value: formatDate(salesOrder.requested_delivery_date) },
                ]}
                primaryPartyLabel="Customer"
                primaryPartyValue={`Customer #${salesOrder.customer_id}`}
                status={salesOrder.status}
                title="Sales document"
            />

            <RelatedDocumentsTabs activeTab={tab} onChange={setTab} tabs={[{ id: 'overview', label: 'Overview' }, { id: 'lines', label: 'Line Items' }, { id: 'timeline', label: 'Timeline' }, { id: 'related', label: 'Related Documents' }]} />

            {tab === 'overview' ? (
                <div className="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
                    <ContentCard>
                        <h3 className="text-lg font-semibold text-stone-950">Document summary</h3>
                        <dl className="mt-4 grid gap-4 sm:grid-cols-2">
                            <div><dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Currency ID</dt><dd className="mt-1 text-sm font-medium text-stone-950">#{salesOrder.currency_id}</dd></div>
                            <div><dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Price List ID</dt><dd className="mt-1 text-sm font-medium text-stone-950">{salesOrder.price_list_id ? `#${salesOrder.price_list_id}` : '-'}</dd></div>
                            <div><dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Exchange Rate</dt><dd className="mt-1 text-sm font-medium text-stone-950">{salesOrder.exchange_rate ?? '-'}</dd></div>
                            <div><dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Created By</dt><dd className="mt-1 text-sm font-medium text-stone-950">{salesOrder.created_by ? `#${salesOrder.created_by}` : '-'}</dd></div>
                        </dl>
                    </ContentCard>
                    <TotalsSummaryCard discountTotal={salesOrder.discount_total} grandTotal={salesOrder.grand_total} subtotal={salesOrder.subtotal} taxTotal={salesOrder.tax_total} />
                </div>
            ) : null}
            {tab === 'lines' ? <DocumentLineItemsTable columns={[]} description="Sales order line items are not included in the current backend show resource." getRowKey={() => 'none'} rows={[]} title="Line items" /> : null}
            {tab === 'timeline' ? <TimelinePlaceholder /> : null}
            {tab === 'related' ? <ContentCard><p className="text-sm text-stone-600">Shipment, invoice, and return linkages can move into this tab when related-document endpoints are exposed for the sales workflow.</p></ContentCard> : null}

            <ContentCard>
                <WorkflowActionBar description="Only expose confirm and cancel actions while the document remains in workflow states that are operationally safe for the available backend action endpoints.">
                    {salesOrder.status === 'draft' ? <Button onClick={() => setAction('confirm')} type="button">Confirm Sales Order</Button> : null}
                    {(salesOrder.status === 'draft' || salesOrder.status === 'confirmed' || salesOrder.status === 'partial') ? (
                        <Button onClick={() => setAction('cancel')} type="button" variant="secondary">Cancel Sales Order</Button>
                    ) : null}
                </WorkflowActionBar>
            </ContentCard>

            <ConfirmWorkflowModal
                confirmLabel={action === 'confirm' ? 'Confirm sales order' : 'Cancel sales order'}
                description={action ? `${action === 'confirm' ? 'Confirm' : 'Cancel'} ${salesOrder.so_number}?` : ''}
                isLoading={confirmMutation.isPending || cancelMutation.isPending}
                onCancel={() => setAction(null)}
                onConfirm={() => void handleActionConfirm()}
                open={Boolean(action)}
                title={action === 'confirm' ? 'Confirm sales order' : 'Cancel sales order'}
            />
        </div>
    );
}
