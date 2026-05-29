import { Link, useParams } from 'react-router-dom';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { Tabs } from '../../../shared/components/ui/Tabs';
import { Button } from '../../../shared/components/ui/Button';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StockAdjustmentLineTable, StockMovementSummaryCard, StockTransferLineTable, SourceReferencePanel } from '../components/InventoryComponents';
import { getAdjustmentById, getMovementById, getTransferById, traceability } from '../mock/inventoryMock';
import { InventoryTraceabilityTimeline } from '../components/InventoryComponents';
import { useState } from 'react';

export function StockMovementDetailPage() {
    const { id } = useParams();
    const [active, setActive] = useState('overview');
    const movement = getMovementById(id ?? '');
    const tabs = ['overview', 'source', 'item', 'warehouse', 'batch', 'quantity', 'cost', 'audit'].map((value) => ({ label: label(value), value }));

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/inventory/movements"><Button variant="secondary">Back</Button></Link>} eyebrow="Inventory Movement" subtitle="Movement detail is readonly. Source module supplies context; Inventory owns stock ledger effects." title={movement.movementNumber} />
            <StockMovementSummaryCard movement={movement} />
            <Tabs active={active} items={tabs} onChange={setActive} />
            {active === 'overview' ? <MovementPreview movement={movement} /> : null}
            {active === 'source' ? <SourceReferencePanel sourceModule={movement.sourceModule} sourceReference={movement.sourceReference} /> : null}
            {active === 'item' ? <PreviewPanel rows={[{ label: 'Item', value: movement.itemName }, { label: 'UOM', value: movement.uom }]} title="Item / UOM" /> : null}
            {active === 'warehouse' ? <PreviewPanel rows={[{ label: 'Warehouse', value: movement.warehouse }, { label: 'Location', value: movement.location }]} title="Warehouse / Location" /> : null}
            {active === 'batch' ? <PreviewPanel rows={[{ label: 'Batch / Serial', value: movement.batchOrSerial ?? 'Not applicable' }]} title="Batch / Serial" /> : null}
            {active === 'quantity' ? <PreviewPanel rows={[{ label: 'Quantity', value: movement.quantity }, { label: 'Effect', value: movement.quantityEffect }]} title="Quantity Effect" /> : null}
            {active === 'cost' ? <PreviewPanel rows={[{ label: 'Cost / valuation', value: movement.costEffect }]} title="Cost / Valuation" /> : null}
            {active === 'audit' ? <InventoryTraceabilityTimeline entries={traceability} /> : null}
        </div>
    );
}

export function StockTransferDetailPage() {
    const { id } = useParams();
    const [active, setActive] = useState('overview');
    const transfer = getTransferById(id ?? '');
    const tabs = ['overview', 'lines', 'source', 'destination', 'workflow', 'audit'].map((value) => ({ label: label(value), value }));

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/inventory/transfers"><Button variant="secondary">Back</Button></Link>} eyebrow="Stock Transfer" subtitle="Backend owns availability checks and source/destination stock effects." title={transfer.transferNumber} />
            <Tabs active={active} items={tabs} onChange={setActive} />
            {active === 'overview' ? <PreviewPanel rows={[{ label: 'Reason', value: transfer.reason }, { label: 'Date', value: transfer.transferDate }, { label: 'Status', value: transfer.status }]} title="Transfer Overview" /> : null}
            {active === 'lines' ? <StockTransferLineTable rows={transfer.lines} /> : null}
            {active === 'source' ? <PreviewPanel rows={[{ label: 'Warehouse', value: transfer.sourceWarehouse }, { label: 'Location', value: transfer.sourceLocation }]} title="Source Warehouse" /> : null}
            {active === 'destination' ? <PreviewPanel rows={[{ label: 'Warehouse', value: transfer.destinationWarehouse }, { label: 'Location', value: transfer.destinationLocation }]} title="Destination Warehouse" /> : null}
            {active === 'workflow' ? <PreviewPanel rows={[{ label: 'Allowed actions', value: 'Backend provided' }, { label: 'Current status', value: transfer.status }]} title="Status / Workflow" /> : null}
            {active === 'audit' ? <InventoryTraceabilityTimeline entries={traceability} /> : null}
        </div>
    );
}

export function StockAdjustmentDetailPage() {
    const { id } = useParams();
    const [active, setActive] = useState('overview');
    const adjustment = getAdjustmentById(id ?? '');
    const tabs = ['overview', 'lines', 'reason', 'impact', 'audit'].map((value) => ({ label: label(value), value }));

    return (
        <div className="space-y-6">
            <PageHeader actions={<Link to="/inventory/adjustments"><Button variant="secondary">Back</Button></Link>} eyebrow="Stock Adjustment" subtitle="Backend owns quantity and valuation effects." title={adjustment.adjustmentNumber} />
            <Tabs active={active} items={tabs} onChange={setActive} />
            {active === 'overview' ? <PreviewPanel rows={[{ label: 'Warehouse', value: adjustment.warehouse }, { label: 'Location', value: adjustment.location }, { label: 'Status', value: adjustment.status }]} title="Adjustment Overview" /> : null}
            {active === 'lines' ? <StockAdjustmentLineTable rows={adjustment.lines} /> : null}
            {active === 'reason' ? <PreviewPanel rows={[{ label: 'Reason', value: adjustment.reason }]} title="Reason" /> : null}
            {active === 'impact' ? <PreviewPanel rows={adjustment.lines.map((line) => ({ label: line.itemName, value: line.quantityImpact }))} title="Quantity Impact" /> : null}
            {active === 'audit' ? <InventoryTraceabilityTimeline entries={traceability} /> : null}
        </div>
    );
}

function MovementPreview({ movement }: { movement: ReturnType<typeof getMovementById> }) {
    return <PreviewPanel rows={[{ label: 'Movement type', value: movement.movementType.replaceAll('_', ' ') }, { label: 'Quantity', value: movement.quantity }, { label: 'Status', value: movement.status }]} title="Movement Overview" />;
}

function label(value: string) {
    return value.split('_').map((part) => part.charAt(0).toUpperCase() + part.slice(1)).join(' ');
}
