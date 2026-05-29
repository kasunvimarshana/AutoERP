import type { ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { Card } from '../../../shared/components/ui/Card';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { DataTable, type DataTableColumn } from '../../../shared/components/data/DataTable';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import type {
    CostLayer,
    CycleCount,
    InventoryAuditEntry,
    InventoryBatch,
    InventorySerial,
    InventoryValuation,
    PickingTask,
    PutAwayTask,
    ReceiptInspection,
    StockAdjustment,
    StockAdjustmentLine,
    StockAvailabilityPreviewResult,
    StockLevel,
    StockMovement,
    StockReservation,
    StockTransfer,
    StockTransferLine,
} from '../types/inventory.types';

export function InventoryDashboardCards({ metrics }: { metrics: Array<{ label: string; status: string; value: string }> }) {
    return (
        <div className="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
            {metrics.map((metric) => (
                <Card className="p-4" key={metric.label}>
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">{metric.label}</p>
                            <p className="mt-3 text-2xl font-bold text-slate-950">{metric.value}</p>
                        </div>
                        <StatusBadge status={metric.status} />
                    </div>
                </Card>
            ))}
        </div>
    );
}

export function StockLevelTable({ rows }: { rows: StockLevel[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Item', key: 'itemName', render: (row) => <div><p className="font-semibold text-slate-950">{row.itemName}</p><p className="text-xs text-slate-400">{row.itemCode}</p></div> },
                { header: 'Warehouse', key: 'warehouse', render: (row) => `${row.warehouse} / ${row.location}` },
                { header: 'Batch / Serial', key: 'batchOrSerial' },
                { header: 'On Hand', key: 'onHand' },
                { header: 'Reserved', key: 'reserved' },
                { header: 'Available', key: 'available' },
                { header: 'UOM', key: 'uom' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
                { header: 'Actions', key: 'actions', render: (row) => <div className="flex gap-2"><Link to={`/inventory/movements?item=${row.id}`}><Button variant="secondary">Movements</Button></Link><Link to="/inventory/availability-preview"><Button variant="ghost">Preview</Button></Link></div> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function StockMovementTable({ rows }: { rows: StockMovement[] }) {
    return (
        <DataTable
            columns={[
                { header: 'Movement #', key: 'movementNumber', render: (row) => <Link className="font-semibold text-slate-950 hover:underline" to={`/inventory/movements/${row.id}`}>{row.movementNumber}</Link> },
                { header: 'Type', key: 'movementType', render: (row) => row.movementType.replaceAll('_', ' ') },
                { header: 'Item', key: 'itemName' },
                { header: 'Quantity', key: 'quantity' },
                { header: 'UOM', key: 'uom' },
                { header: 'Warehouse / Location', key: 'warehouse', render: (row) => `${row.warehouse} / ${row.location}` },
                { header: 'Source', key: 'sourceReference', render: (row) => `${row.sourceModule}: ${row.sourceReference}` },
                { header: 'Date', key: 'movementDate' },
                { header: 'Status', key: 'status', render: (row) => <StatusBadge status={row.status} /> },
            ]}
            getRowKey={(row) => row.id}
            rows={rows}
        />
    );
}

export function StockMovementSummaryCard({ movement }: { movement: StockMovement }) {
    return (
        <Card className="p-5">
            <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{movement.movementType.replaceAll('_', ' ')}</p>
                    <h2 className="mt-1 text-2xl font-bold text-slate-950">{movement.movementNumber}</h2>
                    <p className="mt-2 text-sm text-slate-500">{movement.itemName} · {movement.warehouse} / {movement.location}</p>
                </div>
                <StatusBadge status={movement.status} />
            </div>
        </Card>
    );
}

export function StockReservationTable({ rows }: { rows: StockReservation[] }) {
    return <SimpleTable rows={rows} columns={[['itemName', 'Item'], ['warehouse', 'Warehouse'], ['quantity', 'Qty'], ['uom', 'UOM'], ['reservedFor', 'Reserved For'], ['sourceReference', 'Source'], ['availableDecision', 'Decision'], ['status', 'Status']]} />;
}

export function StockTransferForm() {
    return (
        <form className="space-y-5">
            <FormSection description="Transfer inputs only. Backend checks availability and posts source/destination effects." title="Transfer Header">
                <div className="grid gap-4 md:grid-cols-4">
                    <WarehouseLocationSelector label="Source warehouse/location" />
                    <WarehouseLocationSelector label="Destination warehouse/location" />
                    <Field label="Transfer date"><Input type="date" defaultValue="2026-05-30" /></Field>
                    <Field label="Reason"><Input placeholder="Replenishment, relocation, request..." /></Field>
                </div>
            </FormSection>
            <FormSection description="Line availability preview is backend/mock-owned." title="Transfer Lines">
                <StockTransferLineTable rows={[{ id: 'draft-line', itemName: 'Select item', requestedQuantity: 'Input only', uom: 'UOM' }]} />
                <div className="mt-4 flex justify-end gap-3"><Button variant="secondary">Save draft</Button><Button variant="blue">Submit transfer</Button></div>
            </FormSection>
        </form>
    );
}

export function StockTransferLineTable({ rows }: { rows: StockTransferLine[] }) {
    return <SimpleTable rows={rows} columns={[['itemName', 'Item'], ['requestedQuantity', 'Requested Qty'], ['uom', 'UOM'], ['batchOrSerial', 'Batch / Serial']]} />;
}

export function StockAdjustmentForm() {
    return (
        <form className="space-y-5">
            <FormSection description="Adjustment quantities are captured here. Backend calculates stock and valuation impact." title="Adjustment Header">
                <div className="grid gap-4 md:grid-cols-4">
                    <WarehouseLocationSelector label="Warehouse/location" />
                    <Field label="Adjustment date"><Input type="date" defaultValue="2026-05-30" /></Field>
                    <Field label="Reason"><Input placeholder="Cycle count variance, damage, found stock..." /></Field>
                    <Field label="Notes"><Textarea placeholder="Optional notes" /></Field>
                </div>
            </FormSection>
            <FormSection description="Preview quantity impact is readonly from backend/mock." title="Adjustment Lines">
                <StockAdjustmentLineTable rows={[{ adjustmentType: 'increase', id: 'draft-adj-line', itemName: 'Select item', quantity: 'Input only', quantityImpact: 'Backend preview', uom: 'UOM' }]} />
                <div className="mt-4 flex justify-end gap-3"><Button variant="secondary">Save draft</Button><Button variant="blue">Post adjustment</Button></div>
            </FormSection>
        </form>
    );
}

export function StockAdjustmentLineTable({ rows }: { rows: StockAdjustmentLine[] }) {
    return <SimpleTable rows={rows} columns={[['itemName', 'Item'], ['adjustmentType', 'Type'], ['quantity', 'Qty'], ['uom', 'UOM'], ['quantityImpact', 'Backend Impact']]} />;
}

export function CycleCountTable({ rows }: { rows: CycleCount[] }) {
    return <SimpleTable rows={rows} columns={[['countNumber', 'Count #'], ['warehouse', 'Warehouse'], ['scheduledDate', 'Scheduled'], ['countedDate', 'Counted'], ['lineSummary', 'Lines'], ['variance', 'Variance'], ['status', 'Status']]} />;
}

export function BatchTable({ rows }: { rows: InventoryBatch[] }) {
    return <SimpleTable rows={rows} columns={[['batchNumber', 'Batch #'], ['itemName', 'Item'], ['warehouse', 'Warehouse'], ['location', 'Location'], ['availableQuantity', 'Available'], ['expiryDate', 'Expiry'], ['sourceReference', 'Source'], ['status', 'Status']]} />;
}

export function SerialTable({ rows }: { rows: InventorySerial[] }) {
    return <SimpleTable rows={rows} columns={[['serialNumber', 'Serial #'], ['itemName', 'Item'], ['warehouse', 'Warehouse'], ['location', 'Location'], ['sourceReference', 'Source'], ['status', 'Status']]} />;
}

export function ReceiptInspectionTable({ rows }: { rows: ReceiptInspection[] }) {
    return <SimpleTable rows={rows} columns={[['inspectionNumber', 'Inspection #'], ['itemName', 'Item'], ['sourceReference', 'Source'], ['result', 'Result'], ['status', 'Status']]} />;
}

export function PutAwayTaskTable({ rows }: { rows: PutAwayTask[] }) {
    return <SimpleTable rows={rows} columns={[['itemName', 'Item'], ['quantity', 'Qty'], ['destinationLocation', 'Destination'], ['sourceReference', 'Source'], ['status', 'Status']]} />;
}

export function PickingTaskTable({ rows }: { rows: PickingTask[] }) {
    return <SimpleTable rows={rows} columns={[['itemName', 'Item'], ['quantity', 'Qty'], ['warehouse', 'Warehouse'], ['sourceReference', 'Source'], ['status', 'Status']]} />;
}

export function ValuationTable({ rows }: { rows: InventoryValuation[] }) {
    return <SimpleTable rows={rows} columns={[['itemName', 'Item'], ['warehouse', 'Warehouse'], ['valuationMethod', 'Method'], ['quantity', 'Qty'], ['unitCost', 'Unit Cost'], ['totalValue', 'Total Value'], ['latestCostLayer', 'Latest Layer'], ['updatedAt', 'Updated']]} />;
}

export function CostLayerPanel({ rows }: { rows: CostLayer[] }) {
    return <SimpleTable rows={rows} columns={[['itemName', 'Item'], ['sourceReference', 'Source'], ['layerDate', 'Date'], ['quantity', 'Qty'], ['remainingQuantity', 'Remaining'], ['unitCost', 'Unit Cost']]} />;
}

export function StockAvailabilityPreviewForm() {
    return (
        <FormSection description="Submit item, warehouse, UOM, and quantity context. Backend/mock returns stock decision." title="Availability Context">
            <div className="grid gap-4 md:grid-cols-3">
                <Field label="Item"><Input placeholder="Search item" /></Field>
                <WarehouseLocationSelector label="Warehouse/location" />
                <Field label="UOM"><Input placeholder="PCS, L, BOX..." /></Field>
                <Field label="Requested quantity"><Input inputMode="decimal" placeholder="Quantity" /></Field>
                <Field label="Batch/serial optional"><Input placeholder="Batch or serial" /></Field>
                <Field label="Source module optional"><Select><option value="">None</option><option value="sales">Sales</option><option value="purchase">Purchase</option><option value="vehicle_service">Vehicle Service</option><option value="vehicle_rental">Vehicle Rental</option></Select></Field>
            </div>
            <div className="mt-4 flex justify-end"><Button variant="blue">Preview availability</Button></div>
        </FormSection>
    );
}

export function StockAvailabilityResultPanel({ result }: { result: StockAvailabilityPreviewResult }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Requested quantity', value: result.calculated.requestedQuantity },
                { label: 'Available quantity', value: result.calculated.availableQuantity },
                { label: 'Reserved quantity', value: result.calculated.reservedQuantity },
                { label: 'Decision', value: result.calculated.decision.replaceAll('_', ' ') },
            ]}
            status="Backend Preview"
            subtitle="Readonly preview. Frontend does not calculate availability, reservations, UOM conversion, or stock effects."
            title="Availability Result"
        />
    );
}

export function InventoryTraceabilityTimeline({ entries }: { entries: InventoryAuditEntry[] }) {
    return (
        <div className="space-y-3">
            {entries.map((entry) => (
                <div className="rounded-lg border border-slate-200 bg-white p-4" key={entry.id}>
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <p className="text-sm font-semibold text-slate-950">{entry.description}</p>
                            <p className="mt-1 text-xs font-bold uppercase tracking-wide text-slate-400">{entry.actor} · {entry.type.replaceAll('_', ' ')}</p>
                        </div>
                        <span className="text-xs text-slate-400">{entry.time}</span>
                    </div>
                </div>
            ))}
        </div>
    );
}

export function SourceReferencePanel({ sourceModule, sourceReference }: { sourceModule: string; sourceReference: string }) {
    return <PreviewPanel rows={[{ label: 'Source module', value: sourceModule }, { label: 'Source reference', value: sourceReference }]} status="Readonly" title="Source Reference" />;
}

export function WarehouseLocationSelector({ label }: { label: string }) {
    return (
        <Field label={label}>
            <div className="grid gap-2 md:grid-cols-2">
                <Select><option>Main Warehouse</option><option>Service Store</option><option>Rental Accessories</option></Select>
                <Input placeholder="Location" />
            </div>
        </Field>
    );
}

function SimpleTable<T extends { id: string }>({ columns, rows }: { columns: Array<[keyof T & string, string]>; rows: T[] }) {
    const tableColumns: Array<DataTableColumn<T>> = columns.map(([key, header]) => ({
        header,
        key,
        render: (row) => key.toLowerCase().includes('status') ? <StatusBadge status={String(row[key] ?? '')} /> : String(row[key] ?? ''),
    }));
    return <DataTable columns={tableColumns} getRowKey={(row) => row.id} rows={rows} />;
}

function Field({ children, label }: { children: ReactNode; label: string }) {
    return (
        <label className="space-y-2 text-sm">
            <span className="font-semibold text-slate-700">{label}</span>
            {children}
        </label>
    );
}
