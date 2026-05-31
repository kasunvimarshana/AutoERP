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
    if (!movement) return <EmptyState description="Loading movement from backend." title="Loading movement" />;

    const tabs = ['overview', 'source', 'item', 'warehouse', 'batch', 'quantity', 'cost', 'audit'].map((value) => ({ label: label(value), value }));

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/inventory/movements"><Button variant="secondary">Back</Button></Link>} eyebrow="Inventory Movement" subtitle="Movement detail is readonly. Source module supplies context; Inventory owns stock ledger effects." title={movement.movementNumber} />
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
    if (!transfer) return <EmptyState description="Loading transfer from backend." title="Loading transfer" />;

    const tabs = ['overview', 'lines', 'source', 'destination', 'workflow', 'audit'].map((value) => ({ label: label(value), value }));

    return (
        <div className="space-y-6">
            <PageHeader actions={<><Link to="/inventory/transfers"><Button variant="secondary">Back</Button></Link><Button onClick={() => void inventoryApi.submitTransfer(transfer.id).then(load)} variant="secondary">Submit</Button><Button onClick={() => void inventoryApi.completeTransfer(transfer.id).then(load)} variant="blue">Complete</Button></>} eyebrow="Stock Transfer" subtitle="Backend owns availability checks and source/destination stock effects." title={transfer.transferNumber} />
            <Tabs active={active} items={tabs} onChange={setActive} />
            {active === 'overview' ? <PreviewPanel rows={[{ label: 'Reason', value: transfer.reason }, { label: 'Date', value: transfer.transferDate }, { label: 'Status', value: transfer.status }]} title="Transfer Overview" /> : null}
            {active === 'lines' ? <StockTransferLineTable rows={transfer.lines} /> : null}
            {active === 'source' ? <PreviewPanel rows={[{ label: 'Warehouse', value: transfer.sourceWarehouse }, { label: 'Location', value: transfer.sourceLocation }]} title="Source Warehouse" /> : null}
            {active === 'destination' ? <PreviewPanel rows={[{ label: 'Warehouse', value: transfer.destinationWarehouse }, { label: 'Location', value: transfer.destinationLocation }]} title="Destination Warehouse" /> : null}
            {active === 'workflow' ? <PreviewPanel rows={[{ label: 'Current status', value: transfer.status }, { label: 'Workflow source', value: 'Backend status update' }]} title="Status / Workflow" /> : null}
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
    if (!adjustment) return <EmptyState description="Loading adjustment from backend." title="Loading adjustment" />;

    const tabs = ['overview', 'lines', 'reason', 'impact', 'audit'].map((value) => ({ label: label(value), value }));

    return (
        <div className="space-y-6">
            <PageHeader actions={<><Link to="/inventory/adjustments"><Button variant="secondary">Back</Button></Link><Button onClick={() => void inventoryApi.postAdjustment(adjustment.id).then(load)} variant="blue">Post</Button></>} eyebrow="Stock Adjustment" subtitle="Backend owns quantity and valuation effects." title={adjustment.adjustmentNumber} />
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
