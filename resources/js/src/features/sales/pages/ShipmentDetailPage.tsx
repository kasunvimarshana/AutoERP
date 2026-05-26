import { useState } from 'react';
import { useParams } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { PageHeader } from '../../../components/layout/PageHeader';
import { ConfirmWorkflowModal, DocumentHeader, DocumentLineItemsTable, TimelinePlaceholder, WorkflowActionBar } from '../../shared/workflow';
import { formatDate, parsePositiveInteger } from '../../shared/utils';
import { useProcessShipment, useShipment } from '../hooks';

export function ShipmentDetailPage() {
    const { shipmentId: shipmentIdParam } = useParams();
    const shipmentId = parsePositiveInteger(shipmentIdParam ?? null, 0);
    const [processOpen, setProcessOpen] = useState(false);
    const shipmentQuery = useShipment(shipmentId, shipmentId > 0);
    const processMutation = useProcessShipment(shipmentId);

    if (shipmentId <= 0) {
        return <ErrorState description="The shipment route is missing a valid shipment ID." title="Invalid shipment route" />;
    }

    if (shipmentQuery.isPending) {
        return <LoadingState lines={10} />;
    }

    if (shipmentQuery.isError) {
        return <ErrorState description={shipmentQuery.error.message} title="Unable to load shipment" />;
    }

    const shipment = shipmentQuery.data;

    async function handleProcess() {
        await processMutation.mutateAsync();
        setProcessOpen(false);
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader breadcrumbs={[{ label: 'Sales', href: '/sales/orders' }, { label: 'Shipments', href: '/sales/shipments' }, { label: shipment.shipment_number }]} description="Shipment detail keeps the fulfillment header, carrier context, and process workflow together in a single document workspace." title={shipment.shipment_number} />
            <DocumentHeader dateLabel="Shipped Date" dateValue={shipment.shipped_date} documentNumber={shipment.shipment_number} documentNumberLabel="Shipment" helperText="Shipment lines are not included by the current backend show resource, so the line area stays ready as a placeholder while the operational header and workflow remain usable." metrics={[{ label: 'Customer', value: `#${shipment.customer_id}` }, { label: 'Warehouse', value: `#${shipment.warehouse_id}` }, { label: 'Sales Order', value: shipment.sales_order_id ? `#${shipment.sales_order_id}` : '-' }]} primaryPartyLabel="Customer" primaryPartyValue={`Customer #${shipment.customer_id}`} status={shipment.status} title="Fulfillment document" />
            <div className="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
                <DocumentLineItemsTable columns={[]} description="Shipment lines are not part of the current backend show resource." getRowKey={() => 'none'} rows={[]} title="Line items" />
                <ContentCard>
                    <h3 className="text-lg font-semibold text-stone-950">Shipment summary</h3>
                    <dl className="mt-4 grid gap-4">
                        <div><dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Carrier</dt><dd className="mt-1 text-sm font-medium text-stone-950">{shipment.carrier || '-'}</dd></div>
                        <div><dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Tracking Number</dt><dd className="mt-1 text-sm font-medium text-stone-950">{shipment.tracking_number || '-'}</dd></div>
                        <div><dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Currency ID</dt><dd className="mt-1 text-sm font-medium text-stone-950">#{shipment.currency_id}</dd></div>
                        <div><dt className="text-xs uppercase tracking-[0.14em] text-stone-500">Last Updated</dt><dd className="mt-1 text-sm font-medium text-stone-950">{formatDate(shipment.updated_at)}</dd></div>
                    </dl>
                </ContentCard>
            </div>
            <TimelinePlaceholder />
            <ContentCard>
                <WorkflowActionBar description="Process the shipment only while it remains in draft, picking, or packed states supported by the backend action endpoint.">
                    {(shipment.status === 'draft' || shipment.status === 'picking' || shipment.status === 'packed') ? <Button onClick={() => setProcessOpen(true)} type="button">Process Shipment</Button> : null}
                </WorkflowActionBar>
            </ContentCard>
            <ConfirmWorkflowModal confirmLabel="Process shipment" description={`Process ${shipment.shipment_number}?`} isLoading={processMutation.isPending} onCancel={() => setProcessOpen(false)} onConfirm={() => void handleProcess()} open={processOpen} title="Process shipment" />
        </div>
    );
}
