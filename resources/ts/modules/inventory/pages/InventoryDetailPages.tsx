import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { InventoryTraceabilityTimeline, SourceReferencePanel, StockAdjustmentLineTable, StockMovementSummaryCard, StockTransferLineTable } from '../components/InventoryComponents';
import { inventoryApi } from '../services/inventoryApi';
import type { InventoryAuditEntry, StockAdjustment, StockMovement, StockTransfer } from '../types/inventory.types';

export function StockMovementDetailPage() {
    const { id } = useParams();
    const [active, setActive] = useState('overview');
    const [movement, setMovement] = useState<StockMovement | null>(null);
    const [trace, setTrace] = useState<InventoryAuditEntry[]>([]);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (!id) return;
        Promise.all([inventoryApi.getStockMovement(id), inventoryApi.getTraceability()])
            .then(([movementResponse, traceResponse]) => {
                setMovement(movementResponse.data);
                setTrace(traceResponse.data);
            })
            .catch((caught: unknown) => setError(caught instanceof Error ? caught.message : 'Unable to load movement.'));
    }, [id]);

    if (error) return <EmptyState description={error} title="Unable to load movement" />;
    if (!movement) return <EmptyState description="Loading movement." title="Loading movement" />;

    const tabs = ['overview', 'source', 'item', 'warehouse', 'batch', 'quantity', 'cost', 'audit'].map((value) => ({ label: label(value), value }));

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/inventory/movements"><Button variant="secondary">Back</Button></Link>} eyebrow="Inventory Movement" subtitle="Readonly stock movement ledger entry." title={movement.movementNumber} />
            <StockMovementSummaryCard movement={movement} />
            <Tabs active={active} items={tabs} onChange={setActive} />
            {active === 'overview' ? <PreviewPanel rows={[{ label: 'Movement type', value: movement.movementType.replaceAll('_', ' ') }, { label: 'Quantity', value: movement.quantity }, { label: 'Status', value: movement.status }]} title="Movement Overview" /> : null}
            {active === 'source' ? <SourceReferencePanel sourceModule={movement.sourceModule} sourceReference={movement.sourceReference} /> : null}
            {active === 'item' ? <PreviewPanel rows={[{ label: 'Item', value: movement.itemName }, { label: 'UOM', value: movement.uom }]} title="Item / UOM" /> : null}
            {active === 'warehouse' ? <PreviewPanel rows={[{ label: 'Warehouse', value: movement.warehouse }, { label: 'Location', value: movement.location }]} title="Warehouse / Location" /> : null}
            {active === 'batch' ? <PreviewPanel rows={[{ label: 'Batch / Serial', value: movement.batchOrSerial ?? 'Not applicable' }]} title="Batch / Serial" /> : null}
            {active === 'quantity' ? <PreviewPanel rows={[{ label: 'Quantity', value: movement.quantity }, { label: 'Effect', value: movement.quantityEffect }]} title="Quantity Effect" /> : null}
            {active === 'cost' ? <PreviewPanel rows={[{ label: 'Cost / valuation', value: movement.costEffect }]} title="Cost / Valuation" /> : null}
            {active === 'audit' ? <InventoryTraceabilityTimeline entries={trace} /> : null}
        </div>
    );
}

export function StockTransferDetailPage() {
    const { id } = useParams();
    const [active, setActive] = useState('overview');
    const [transfer, setTransfer] = useState<StockTransfer | null>(null);
    const [trace, setTrace] = useState<InventoryAuditEntry[]>([]);
    const [error, setError] = useState<string | null>(null);
    const [actionError, setActionError] = useState<string | null>(null);
    const [isActing, setIsActing] = useState(false);

    function load() {
        if (!id) return;
        Promise.all([inventoryApi.getTransfer(id), inventoryApi.getTraceability()])
            .then(([transferResponse, traceResponse]) => {
                setTransfer(transferResponse.data);
                setTrace(traceResponse.data);
            })
            .catch((caught: unknown) => setError(caught instanceof Error ? caught.message : 'Unable to load transfer.'));
    }

    useEffect(load, [id]);

    if (error) return <EmptyState description={error} title="Unable to load transfer" />;
    if (!transfer) return <EmptyState description="Loading transfer." title="Loading transfer" />;

    const tabs = ['overview', 'lines', 'source', 'destination', 'workflow', 'audit'].map((value) => ({ label: label(value), value }));
    const canSubmit = transfer.status === 'draft';
    const canComplete = ['draft', 'pending', 'approved'].includes(transfer.status);

    async function runTransferAction(action: 'complete' | 'submit') {
        if (!transfer) return;
        setActionError(null);
        setIsActing(true);
        try {
            if (action === 'submit') {
                await inventoryApi.submitTransfer(transfer.id);
            } else {
                await inventoryApi.completeTransfer(transfer.id);
            }
            load();
        } catch (caught) {
            setActionError(caught instanceof Error ? caught.message : 'Unable to update transfer status.');
        } finally {
            setIsActing(false);
        }
    }

    return (
        <div className="space-y-6">
            <PageHeader actions={<><Link to="/inventory/transfers"><Button variant="secondary">Back</Button></Link><Button disabled={!canSubmit || isActing} onClick={() => void runTransferAction('submit')} title={canSubmit ? 'Submit transfer' : 'Only draft transfers can be submitted'} variant="secondary">Submit</Button><Button disabled={!canComplete || isActing} onClick={() => void runTransferAction('complete')} title={canComplete ? 'Complete transfer' : 'This transfer cannot be completed from its current status'} variant="blue">Complete</Button></>} eyebrow="Stock Transfer" subtitle="Warehouse transfer detail and lines." title={transfer.transferNumber} />
            {actionError ? <EmptyState description={actionError} title="Action failed" /> : null}
            <Tabs active={active} items={tabs} onChange={setActive} />
            {active === 'overview' ? <PreviewPanel rows={[{ label: 'Reason', value: transfer.reason }, { label: 'Date', value: transfer.transferDate }, { label: 'Status', value: transfer.status }]} title="Transfer Overview" /> : null}
            {active === 'lines' ? <StockTransferLineTable rows={transfer.lines} /> : null}
            {active === 'source' ? <PreviewPanel rows={[{ label: 'Warehouse', value: transfer.sourceWarehouse }, { label: 'Location', value: transfer.sourceLocation }]} title="Source Warehouse" /> : null}
            {active === 'destination' ? <PreviewPanel rows={[{ label: 'Warehouse', value: transfer.destinationWarehouse }, { label: 'Location', value: transfer.destinationLocation }]} title="Destination Warehouse" /> : null}
            {active === 'workflow' ? <PreviewPanel rows={[{ label: 'Current status', value: transfer.status }, { label: 'Next actions', value: canComplete ? 'Submit or complete' : 'No transition available' }]} title="Status / Workflow" /> : null}
            {active === 'audit' ? <InventoryTraceabilityTimeline entries={trace} /> : null}
        </div>
    );
}

export function StockAdjustmentDetailPage() {
    const { id } = useParams();
    const [active, setActive] = useState('overview');
    const [adjustment, setAdjustment] = useState<StockAdjustment | null>(null);
    const [trace, setTrace] = useState<InventoryAuditEntry[]>([]);
    const [error, setError] = useState<string | null>(null);
    const [actionError, setActionError] = useState<string | null>(null);
    const [isActing, setIsActing] = useState(false);

    function load() {
        if (!id) return;
        Promise.all([inventoryApi.getAdjustment(id), inventoryApi.getTraceability()])
            .then(([adjustmentResponse, traceResponse]) => {
                setAdjustment(adjustmentResponse.data);
                setTrace(traceResponse.data);
            })
            .catch((caught: unknown) => setError(caught instanceof Error ? caught.message : 'Unable to load adjustment.'));
    }

    useEffect(load, [id]);

    if (error) return <EmptyState description={error} title="Unable to load adjustment" />;
    if (!adjustment) return <EmptyState description="Loading adjustment." title="Loading adjustment" />;

    const tabs = ['overview', 'lines', 'reason', 'impact', 'audit'].map((value) => ({ label: label(value), value }));
    const canPost = adjustment.status === 'draft';

    async function postAdjustment() {
        if (!adjustment) return;
        setActionError(null);
        setIsActing(true);
        try {
            await inventoryApi.postAdjustment(adjustment.id);
            load();
        } catch (caught) {
            setActionError(caught instanceof Error ? caught.message : 'Unable to post adjustment.');
        } finally {
            setIsActing(false);
        }
    }

    return (
        <div className="space-y-6">
            <PageHeader actions={<><Link to="/inventory/adjustments"><Button variant="secondary">Back</Button></Link><Button disabled={!canPost || isActing} onClick={() => void postAdjustment()} title={canPost ? 'Post adjustment' : 'Only draft adjustments can be posted'} variant="blue">Post</Button></>} eyebrow="Stock Adjustment" subtitle="Stock adjustment detail and quantity impact." title={adjustment.adjustmentNumber} />
            {actionError ? <EmptyState description={actionError} title="Action failed" /> : null}
            <Tabs active={active} items={tabs} onChange={setActive} />
            {active === 'overview' ? <PreviewPanel rows={[{ label: 'Warehouse', value: adjustment.warehouse }, { label: 'Location', value: adjustment.location }, { label: 'Status', value: adjustment.status }]} title="Adjustment Overview" /> : null}
            {active === 'lines' ? <StockAdjustmentLineTable rows={adjustment.lines} /> : null}
            {active === 'reason' ? <PreviewPanel rows={[{ label: 'Reason', value: adjustment.reason }]} title="Reason" /> : null}
            {active === 'impact' ? <PreviewPanel rows={adjustment.lines.map((line) => ({ label: line.itemName, value: line.quantityImpact }))} title="Quantity Impact" /> : null}
            {active === 'audit' ? <InventoryTraceabilityTimeline entries={trace} /> : null}
        </div>
    );
}

function label(value: string) {
    return value.split('_').map((part) => part.charAt(0).toUpperCase() + part.slice(1)).join(' ');
}
