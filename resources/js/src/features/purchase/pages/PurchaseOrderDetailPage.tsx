import { useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { ContentCard } from '../../../components/ui/ContentCard';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { PageHeader } from '../../../components/layout/PageHeader';
import { Button } from '../../../components/ui/Button';
import { ConfirmWorkflowModal, DocumentHeader, DocumentLineItemsTable, RelatedDocumentsTabs, TimelinePlaceholder, TotalsSummaryCard, WorkflowActionBar } from '../../shared/workflow';
import { formatCurrency, formatDate, parsePositiveInteger } from '../../shared/utils';
import { useCancelPurchaseOrder, useConfirmPurchaseOrder, usePurchaseOrder } from '../hooks';
import type { PurchaseOrderLineRecord } from '../types';

function lineTotal(line: PurchaseOrderLineRecord) {
    return Number(line.line_total_with_tax ?? line.line_total ?? 0);
}

export function PurchaseOrderDetailPage() {
    const { purchaseOrderId: purchaseOrderIdParam } = useParams();
    const purchaseOrderId = parsePositiveInteger(purchaseOrderIdParam ?? null, 0);
    const [tab, setTab] = useState('overview');
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [cancelOpen, setCancelOpen] = useState(false);
    const purchaseOrderQuery = usePurchaseOrder(purchaseOrderId, purchaseOrderId > 0);
    const confirmMutation = useConfirmPurchaseOrder(purchaseOrderId);
    const cancelMutation = useCancelPurchaseOrder(purchaseOrderId);

    if (purchaseOrderId <= 0) {
        return <ErrorState description="The purchase order route is missing a valid purchase order ID." title="Invalid purchase order route" />;
    }

    if (purchaseOrderQuery.isPending) {
        return <LoadingState lines={10} />;
    }

    if (purchaseOrderQuery.isError) {
        return <ErrorState description={purchaseOrderQuery.error.message} title="Unable to load purchase order" />;
    }

    const purchaseOrder = purchaseOrderQuery.data;
    const lines = purchaseOrder.lines ?? purchaseOrder.purchase_order_lines ?? [];
    const lineColumns = [
        { key: 'item_id', header: 'Item', render: (line: PurchaseOrderLineRecord) => <span className="text-sm text-stone-700">#{line.item_id}</span> },
        { key: 'description', header: 'Description', render: (line: PurchaseOrderLineRecord) => <span className="text-sm text-stone-700">{line.description ?? '-'}</span> },
        { key: 'uom_id', header: 'UOM', render: (line: PurchaseOrderLineRecord) => <span className="text-sm text-stone-700">#{line.uom_id}</span> },
        { key: 'ordered_qty', header: 'Ordered', render: (line: PurchaseOrderLineRecord) => <span className="text-sm text-stone-700">{line.ordered_qty}</span> },
        { key: 'received_qty', header: 'Received', render: (line: PurchaseOrderLineRecord) => <span className="text-sm text-stone-700">{line.received_qty}</span> },
        { key: 'unit_price', header: 'Unit Price', render: (line: PurchaseOrderLineRecord) => <span className="text-sm text-stone-700">{formatCurrency(line.unit_price)}</span> },
        { key: 'line_total', header: 'Line Total', render: (line: PurchaseOrderLineRecord) => <span className="text-sm font-medium text-stone-950">{formatCurrency(lineTotal(line))}</span> },
    ];

    async function handleConfirm() {
        await confirmMutation.mutateAsync();
        setConfirmOpen(false);
    }

    async function handleCancel() {
        await cancelMutation.mutateAsync();
        setCancelOpen(false);
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader breadcrumbs={[{ label: 'Purchase', href: '/purchase/orders' }, { label: 'Purchase Orders', href: '/purchase/orders' }, { label: purchaseOrder.po_number }]} description="Purchase order detail keeps supplier, warehouse, totals, and confirmation workflow together in one ERP document view." title={purchaseOrder.po_number} />

            <DocumentHeader
                dateLabel="Order Date"
                dateValue={purchaseOrder.order_date}
                documentNumber={purchaseOrder.po_number}
                documentNumberLabel="Purchase Order"
                helperText="Line items, totals, and workflow status are loaded from the purchase order resource."
                metrics={[
                    { label: 'Supplier', value: `#${purchaseOrder.supplier_id}` },
                    { label: 'Warehouse', value: `#${purchaseOrder.warehouse_id}` },
                    { label: 'Expected Date', value: formatDate(purchaseOrder.expected_date) },
                ]}
                primaryPartyLabel="Supplier"
                primaryPartyValue={`Supplier #${purchaseOrder.supplier_id}`}
                status={purchaseOrder.status}
                title="Purchase document"
            />

            <RelatedDocumentsTabs activeTab={tab} onChange={setTab} tabs={[{ id: 'overview', label: 'Overview' }, { id: 'lines', label: 'Line Items' }, { id: 'timeline', label: 'Timeline' }, { id: 'related', label: 'Related Documents' }]} />

            {tab === 'overview' ? (
                <div className="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
                    <ContentCard>
                        <h3 className="text-lg font-semibold text-stone-950">Document summary</h3>
                        <dl className="mt-4 grid gap-4 sm:grid-cols-2">
                            <div><dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Currency ID</dt><dd className="mt-1 text-sm font-medium text-stone-950">#{purchaseOrder.currency_id}</dd></div>
                            <div><dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Exchange Rate</dt><dd className="mt-1 text-sm font-medium text-stone-950">{purchaseOrder.exchange_rate ?? '-'}</dd></div>
                            <div><dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Created By</dt><dd className="mt-1 text-sm font-medium text-stone-950">{purchaseOrder.created_by ? `#${purchaseOrder.created_by}` : '-'}</dd></div>
                            <div><dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Invoice Status</dt><dd className="mt-1 text-sm font-medium text-stone-950">{purchaseOrder.invoice_status.replaceAll('_', ' ')}</dd></div>
                        </dl>
                    </ContentCard>
                    <TotalsSummaryCard discountTotal={purchaseOrder.discount_total} grandTotal={purchaseOrder.grand_total} subtotal={purchaseOrder.subtotal} taxTotal={purchaseOrder.tax_total} />
                </div>
            ) : null}
            {tab === 'lines' ? <DocumentLineItemsTable columns={lineColumns} description="Item lines captured for this purchase order." getRowKey={(line) => line.id} rows={lines} title="Line items" emptyDescription="No item lines have been added to this purchase order." /> : null}
            {tab === 'timeline' ? <TimelinePlaceholder /> : null}
            {tab === 'related' ? <ContentCard><p className="text-sm text-stone-600">Related GRN, invoice, and return linkage can be added when related-document endpoints are exposed by the backend.</p></ContentCard> : null}

            <ContentCard>
                <WorkflowActionBar description="Only show confirmation when the purchase order is still in a pre-confirmed workflow state supported by the backend action endpoint.">
                    <Link to={`/purchase/orders/${purchaseOrder.id}/edit`}><Button type="button" variant="secondary">Edit Purchase Order</Button></Link>
                    <Link to={`/purchase/grns/new?purchaseOrderId=${purchaseOrder.id}`}><Button type="button" variant="secondary">Create GRN</Button></Link>
                    {purchaseOrder.status === 'draft' ? <Button onClick={() => setConfirmOpen(true)} type="button">Confirm Purchase Order</Button> : null}
                    {purchaseOrder.status !== 'cancelled' && purchaseOrder.status !== 'received' ? <Button onClick={() => setCancelOpen(true)} type="button" variant="secondary">Cancel Purchase Order</Button> : null}
                </WorkflowActionBar>
            </ContentCard>

            <ConfirmWorkflowModal confirmLabel="Confirm purchase order" description={`Confirm ${purchaseOrder.po_number}?`} isLoading={confirmMutation.isPending} onCancel={() => setConfirmOpen(false)} onConfirm={() => void handleConfirm()} open={confirmOpen} title="Confirm purchase order" />
            <ConfirmWorkflowModal confirmLabel="Cancel purchase order" description={`Cancel ${purchaseOrder.po_number}?`} isLoading={cancelMutation.isPending} onCancel={() => setCancelOpen(false)} onConfirm={() => void handleCancel()} open={cancelOpen} title="Cancel purchase order" />
        </div>
    );
}
