import { useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { PageHeader } from '../../../components/layout/PageHeader';
import { ConfirmWorkflowModal, DocumentHeader, DocumentLineItemsTable, TimelinePlaceholder, TotalsSummaryCard, WorkflowActionBar } from '../../shared/workflow';
import { formatCurrency, parsePositiveInteger } from '../../shared/utils';
import { useGrn, usePostGrn } from '../hooks';
import type { GrnLineRecord } from '../types';

function lineTotal(line: GrnLineRecord) {
    return Number(line.line_total_with_tax ?? line.received_qty ?? 0) || Number(line.received_qty ?? 0) * Number(line.unit_price ?? 0);
}

export function GrnDetailPage() {
    const { grnId: grnIdParam } = useParams();
    const grnId = parsePositiveInteger(grnIdParam ?? null, 0);
    const [postOpen, setPostOpen] = useState(false);
    const grnQuery = useGrn(grnId, grnId > 0);
    const postMutation = usePostGrn(grnId);

    if (grnId <= 0) {
        return <ErrorState description="The GRN route is missing a valid GRN ID." title="Invalid GRN route" />;
    }

    if (grnQuery.isPending) {
        return <LoadingState lines={10} />;
    }

    if (grnQuery.isError) {
        return <ErrorState description={grnQuery.error.message} title="Unable to load GRN" />;
    }

    const grn = grnQuery.data;
    const lines = grn.lines ?? grn.grn_lines ?? [];
    const receivedValue = lines.reduce((total, line) => total + lineTotal(line), 0);
    const lineColumns = [
        { key: 'item_id', header: 'Item', render: (line: GrnLineRecord) => <span className="text-sm text-stone-700">#{line.item_id}</span> },
        { key: 'location_id', header: 'Location', render: (line: GrnLineRecord) => <span className="text-sm text-stone-700">#{line.location_id}</span> },
        { key: 'uom_id', header: 'UOM', render: (line: GrnLineRecord) => <span className="text-sm text-stone-700">#{line.uom_id}</span> },
        { key: 'expected_qty', header: 'Ordered', render: (line: GrnLineRecord) => <span className="text-sm text-stone-700">{line.expected_qty}</span> },
        { key: 'received_qty', header: 'Received', render: (line: GrnLineRecord) => <span className="text-sm text-stone-700">{line.received_qty}</span> },
        { key: 'unit_price', header: 'Unit Cost', render: (line: GrnLineRecord) => <span className="text-sm text-stone-700">{formatCurrency(line.unit_price)}</span> },
        { key: 'line_total', header: 'Line Total', render: (line: GrnLineRecord) => <span className="text-sm font-medium text-stone-950">{formatCurrency(lineTotal(line))}</span> },
    ];

    async function handlePost() {
        await postMutation.mutateAsync();
        setPostOpen(false);
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader breadcrumbs={[{ label: 'Purchase', href: '/purchase/grns' }, { label: 'GRNs', href: '/purchase/grns' }, { label: grn.grn_number }]} description="GRN detail keeps warehouse receipt context, received lines, and posting action together." title={grn.grn_number} />
            <DocumentHeader dateLabel="Received Date" dateValue={grn.received_date} documentNumber={grn.grn_number} documentNumberLabel="GRN" helperText="Received product lines are loaded from the GRN resource." metrics={[{ label: 'Supplier', value: `#${grn.supplier_id}` }, { label: 'Warehouse', value: `#${grn.warehouse_id}` }, { label: 'PO', value: grn.purchase_order_id ? `#${grn.purchase_order_id}` : '-' }]} primaryPartyLabel="Supplier" primaryPartyValue={`Supplier #${grn.supplier_id}`} status={grn.status} title="Receipt document" />
            <div className="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
                <DocumentLineItemsTable columns={lineColumns} description="Product line items received on this GRN." getRowKey={(line) => line.id} rows={lines} title="Line items" emptyDescription="No product lines have been added to this GRN." />
                <TotalsSummaryCard grandTotal={receivedValue} subtotal={receivedValue} taxTotal={null} />
            </div>
            <TimelinePlaceholder />
            <ContentCard>
                <WorkflowActionBar description="Post the GRN only while it remains in a non-posted state supported by the backend action endpoint.">
                    {grn.status === 'draft' ? <Link to={`/purchase/grns/${grn.id}/edit`}><Button type="button" variant="secondary">Edit GRN</Button></Link> : null}
                    {grn.status === 'draft' ? <Button onClick={() => setPostOpen(true)} type="button">Confirm GRN</Button> : null}
                </WorkflowActionBar>
            </ContentCard>
            <ConfirmWorkflowModal confirmLabel="Confirm GRN" description={`Confirm ${grn.grn_number}?`} isLoading={postMutation.isPending} onCancel={() => setPostOpen(false)} onConfirm={() => void handlePost()} open={postOpen} title="Confirm GRN" />
        </div>
    );
}
