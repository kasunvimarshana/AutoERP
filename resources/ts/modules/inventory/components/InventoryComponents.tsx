import { useEffect, useMemo, useState, type FormEvent, type ReactNode } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { ApiError } from '../../../services/api/apiErrors';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { DataTable, type DataTableColumn } from '../../../shared/components/data/DataTable';
import { FieldError } from '../../../shared/components/forms/FieldError';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { inventoryApi } from '../services/inventoryApi';
import type {
    CostLayer,
    CycleCount,
    InventoryAuditEntry,
    InventoryBatch,
    InventoryLookupOption,
    InventorySerial,
    InventoryValuation,
    PickingTask,
    PutAwayTask,
    ReceiptInspection,
    StockAdjustment,
    StockAdjustmentFormInput,
    StockAdjustmentLine,
    StockAvailabilityPreviewRequest,
    StockAvailabilityPreviewResult,
    StockLevel,
    StockMovement,
    StockReservation,
    StockTransfer,
    StockTransferFormInput,
    StockTransferLine,
} from '../types/inventory.types';

type FieldErrors = Record<string, string[]>;

export function InventoryDashboardCards({ metrics }: { metrics: Array<{ label: string; status: string; value: string }> }) {
    return (
        <div className="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
            {metrics.map((metric) => (
                <Card className="p-4" key={metric.label}>
                    <p className="text-xs font-semibold uppercase text-slate-400">{metric.label}</p>
                    <div className="mt-3 flex items-end justify-between gap-3">
                        <p className="text-2xl font-bold text-slate-950">{metric.value}</p>
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
                { header: 'Actions', key: 'actions', render: () => <Link to="/inventory/availability-preview"><Button variant="secondary">Preview</Button></Link> },
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
                    <p className="text-xs font-bold uppercase text-slate-400">{movement.movementType.replaceAll('_', ' ')}</p>
                    <h2 className="mt-1 text-2xl font-bold text-slate-950">{movement.movementNumber}</h2>
                    <p className="mt-2 text-sm text-slate-500">{movement.itemName} / {movement.warehouse} / {movement.location}</p>
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
    const navigate = useNavigate();
    const [lookups, setLookups] = useState<LookupState>({ items: [], locations: [], uoms: [], warehouses: [] });
    const [error, setError] = useState<string | null>(null);
    const [fieldErrors, setFieldErrors] = useState<FieldErrors>({});
    const [isSaving, setIsSaving] = useState(false);

    useEffect(() => {
        void loadLookups().then(setLookups).catch((caught: unknown) => setError(errorMessage(caught, 'Unable to load inventory lookups.')));
    }, []);

    async function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setError(null);
        setFieldErrors({});
        setIsSaving(true);

        const form = new FormData(event.currentTarget);
        const submitter = (event.nativeEvent as SubmitEvent).submitter as HTMLButtonElement | null;
        const input: StockTransferFormInput = {
            fromLocationId: stringValue(form, 'fromLocationId'),
            fromWarehouseId: stringValue(form, 'fromWarehouseId'),
            lines: [{
                itemId: stringValue(form, 'itemId'),
                quantity: stringValue(form, 'quantity'),
                toLocationId: stringValue(form, 'toLocationId'),
                uomId: stringValue(form, 'uomId'),
            }],
            notes: stringValue(form, 'notes'),
            status: submitter?.value === 'complete' ? 'COMPLETED' : 'DRAFT',
            toLocationId: stringValue(form, 'toLocationId'),
            toWarehouseId: stringValue(form, 'toWarehouseId'),
        };

        try {
            const response = await inventoryApi.createTransfer(input);
            navigate(`/inventory/transfers/${response.data.id}`);
        } catch (caught) {
            captureError(caught, setError, setFieldErrors);
        } finally {
            setIsSaving(false);
        }
    }

    return (
        <form className="space-y-5" onSubmit={submit}>
            <GlobalError message={error} />
            <FormSection description="Transfer inputs are persisted by the backend; completing a transfer posts source and destination stock effects." title="Transfer Header">
                <div className="grid gap-4 md:grid-cols-4">
                    <OptionField error={fieldErrors.from_warehouse_id?.[0]} label="Source warehouse" name="fromWarehouseId" options={lookups.warehouses} />
                    <OptionField label="Source location" name="fromLocationId" options={lookups.locations} />
                    <OptionField error={fieldErrors.to_warehouse_id?.[0]} label="Destination warehouse" name="toWarehouseId" options={lookups.warehouses} />
                    <OptionField label="Destination location" name="toLocationId" options={lookups.locations} />
                </div>
            </FormSection>
            <FormSection description="Backend validates stockable item, UOM compatibility, and quantity before saving." title="Transfer Line">
                <div className="grid gap-4 md:grid-cols-4">
                    <OptionField error={fieldErrors['lines.0.item_id']?.[0]} label="Item" name="itemId" options={lookups.items} />
                    <OptionField error={fieldErrors['lines.0.uom_id']?.[0]} label="UOM" name="uomId" options={lookups.uoms} />
                    <TextField error={fieldErrors['lines.0.quantity']?.[0]} inputMode="decimal" label="Quantity" name="quantity" type="number" />
                    <TextAreaField label="Notes" name="notes" />
                </div>
                <div className="mt-4 flex justify-end gap-3">
                    <Button disabled={isSaving} name="action" type="submit" value="draft" variant="secondary">Save draft</Button>
                    <Button disabled={isSaving} name="action" type="submit" value="complete" variant="blue">Complete transfer</Button>
                </div>
            </FormSection>
        </form>
    );
}

export function StockTransferLineTable({ rows }: { rows: StockTransferLine[] }) {
    return rows.length ? <SimpleTable rows={rows} columns={[['itemName', 'Item'], ['requestedQuantity', 'Requested Qty'], ['uom', 'UOM'], ['batchOrSerial', 'Batch / Serial']]} /> : <EmptyState description="No transfer lines returned by backend." title="No lines" />;
}

export function StockAdjustmentForm() {
    const navigate = useNavigate();
    const [lookups, setLookups] = useState<LookupState>({ items: [], locations: [], uoms: [], warehouses: [] });
    const [error, setError] = useState<string | null>(null);
    const [fieldErrors, setFieldErrors] = useState<FieldErrors>({});
    const [isSaving, setIsSaving] = useState(false);

    useEffect(() => {
        void loadLookups().then(setLookups).catch((caught: unknown) => setError(errorMessage(caught, 'Unable to load inventory lookups.')));
    }, []);

    async function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setError(null);
        setFieldErrors({});
        setIsSaving(true);

        const form = new FormData(event.currentTarget);
        const submitter = (event.nativeEvent as SubmitEvent).submitter as HTMLButtonElement | null;
        const input: StockAdjustmentFormInput = {
            lines: [{
                adjustmentQuantity: stringValue(form, 'quantity'),
                direction: stringValue(form, 'direction') === 'DECREASE' ? 'DECREASE' : 'INCREASE',
                itemId: stringValue(form, 'itemId'),
                uomId: stringValue(form, 'uomId'),
            }],
            locationId: stringValue(form, 'locationId'),
            reason: stringValue(form, 'reason'),
            status: submitter?.value === 'post' ? 'COMPLETED' : 'DRAFT',
            warehouseId: stringValue(form, 'warehouseId'),
        };

        try {
            const response = await inventoryApi.createAdjustment(input);
            navigate(`/inventory/adjustments/${response.data.id}`);
        } catch (caught) {
            captureError(caught, setError, setFieldErrors);
        } finally {
            setIsSaving(false);
        }
    }

    return (
        <form className="space-y-5" onSubmit={submit}>
            <GlobalError message={error} />
            <FormSection description="Adjustment quantities are captured here; backend owns quantity and valuation effects." title="Adjustment Header">
                <div className="grid gap-4 md:grid-cols-4">
                    <OptionField error={fieldErrors.warehouse_id?.[0]} label="Warehouse" name="warehouseId" options={lookups.warehouses} />
                    <OptionField label="Location" name="locationId" options={lookups.locations} />
                    <Field label="Direction"><Select name="direction"><option value="INCREASE">Increase</option><option value="DECREASE">Decrease</option></Select></Field>
                    <TextAreaField error={fieldErrors.reason?.[0]} label="Reason" name="reason" />
                </div>
            </FormSection>
            <FormSection description="Backend validates stockable item, UOM compatibility, and signed quantity." title="Adjustment Line">
                <div className="grid gap-4 md:grid-cols-3">
                    <OptionField error={fieldErrors['lines.0.item_id']?.[0]} label="Item" name="itemId" options={lookups.items} />
                    <OptionField error={fieldErrors['lines.0.uom_id']?.[0]} label="UOM" name="uomId" options={lookups.uoms} />
                    <TextField error={fieldErrors['lines.0.adjustment_quantity']?.[0]} inputMode="decimal" label="Quantity" name="quantity" type="number" />
                </div>
                <div className="mt-4 flex justify-end gap-3">
                    <Button disabled={isSaving} name="action" type="submit" value="draft" variant="secondary">Save draft</Button>
                    <Button disabled={isSaving} name="action" type="submit" value="post" variant="blue">Post adjustment</Button>
                </div>
            </FormSection>
        </form>
    );
}

export function StockAdjustmentLineTable({ rows }: { rows: StockAdjustmentLine[] }) {
    return rows.length ? <SimpleTable rows={rows} columns={[['itemName', 'Item'], ['adjustmentType', 'Type'], ['quantity', 'Qty'], ['uom', 'UOM'], ['quantityImpact', 'Backend Impact']]} /> : <EmptyState description="No adjustment lines returned by backend." title="No lines" />;
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
    return rows.length ? <SimpleTable rows={rows} columns={[['itemName', 'Item'], ['sourceReference', 'Source'], ['layerDate', 'Date'], ['quantity', 'Qty'], ['remainingQuantity', 'Remaining'], ['unitCost', 'Unit Cost']]} /> : <EmptyState description="No cost layers returned by backend." title="No cost layers" />;
}

export function StockAvailabilityPreviewForm({ onResult }: { onResult: (result: StockAvailabilityPreviewResult) => void }) {
    const [lookups, setLookups] = useState<LookupState>({ items: [], locations: [], uoms: [], warehouses: [] });
    const [error, setError] = useState<string | null>(null);
    const [isLoading, setIsLoading] = useState(false);

    useEffect(() => {
        void loadLookups().then(setLookups).catch((caught: unknown) => setError(errorMessage(caught, 'Unable to load inventory lookups.')));
    }, []);

    async function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setError(null);
        setIsLoading(true);

        const form = new FormData(event.currentTarget);
        const input: StockAvailabilityPreviewRequest = {
            itemId: stringValue(form, 'itemId'),
            location: stringValue(form, 'locationId'),
            quantity: stringValue(form, 'quantity'),
            sourceModule: stringValue(form, 'sourceModule'),
            uom: stringValue(form, 'uomId'),
            warehouse: stringValue(form, 'warehouseId'),
        };

        try {
            const response = await inventoryApi.previewStockAvailability(input);
            onResult({
                breakdown: response.breakdown.map((row) => ({ label: String(row.label), value: String(row.value) })),
                calculated: response.calculated,
                errors: response.errors,
                input: response.input,
                warnings: response.warnings,
            });
        } catch (caught) {
            setError(errorMessage(caught, 'Unable to preview stock availability.'));
        } finally {
            setIsLoading(false);
        }
    }

    return (
        <form onSubmit={submit}>
            <FormSection description="Submit item, warehouse, UOM, and quantity context. Backend returns the stock decision." title="Availability Context">
                <GlobalError message={error} />
                <div className="grid gap-4 md:grid-cols-3">
                    <OptionField label="Item" name="itemId" options={lookups.items} />
                    <OptionField label="Warehouse" name="warehouseId" options={lookups.warehouses} />
                    <OptionField label="Location" name="locationId" options={lookups.locations} />
                    <OptionField label="UOM" name="uomId" options={lookups.uoms} />
                    <TextField inputMode="decimal" label="Requested quantity" name="quantity" type="number" />
                    <Field label="Source module"><Select name="sourceModule"><option value="">None</option><option value="sales">Sales</option><option value="purchase">Purchase</option><option value="vehicle_service">Vehicle Service</option><option value="vehicle_rental">Vehicle Rental</option></Select></Field>
                </div>
                <div className="mt-4 flex justify-end"><Button disabled={isLoading} type="submit" variant="blue">Preview availability</Button></div>
            </FormSection>
        </form>
    );
}

export function StockAvailabilityResultPanel({ result }: { result: StockAvailabilityPreviewResult | null }) {
    if (!result) {
        return <EmptyState description="Submit item and warehouse context to request a backend availability preview." title="No preview yet" />;
    }

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
    return entries.length ? (
        <div className="space-y-3">
            {entries.map((entry) => (
                <div className="rounded-lg border border-slate-200 bg-white p-4" key={entry.id}>
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <p className="text-sm font-semibold text-slate-950">{entry.description}</p>
                            <p className="mt-1 text-xs font-bold uppercase text-slate-400">{entry.actor} / {entry.type.replaceAll('_', ' ')}</p>
                        </div>
                        <span className="text-xs text-slate-400">{entry.time}</span>
                    </div>
                </div>
            ))}
        </div>
    ) : <EmptyState description="No traceability entries returned by backend." title="No trace entries" />;
}

export function SourceReferencePanel({ sourceModule, sourceReference }: { sourceModule: string; sourceReference: string }) {
    return <PreviewPanel rows={[{ label: 'Source module', value: sourceModule }, { label: 'Source reference', value: sourceReference }]} status="Readonly" title="Source Reference" />;
}

type LookupState = {
    items: InventoryLookupOption[];
    locations: InventoryLookupOption[];
    uoms: InventoryLookupOption[];
    warehouses: InventoryLookupOption[];
};

async function loadLookups(): Promise<LookupState> {
    const [items, locations, uoms, warehouses] = await Promise.all([
        inventoryApi.listItems(),
        inventoryApi.listLocations(),
        inventoryApi.listUoms(),
        inventoryApi.listWarehouses(),
    ]);

    return {
        items: items.data,
        locations: locations.data,
        uoms: uoms.data,
        warehouses: warehouses.data,
    };
}

function SimpleTable<T extends { id: string }>({ columns, rows }: { columns: Array<[keyof T & string, string]>; rows: T[] }) {
    const tableColumns: Array<DataTableColumn<T>> = columns.map(([key, header]) => ({
        header,
        key,
        render: (row) => key.toLowerCase().includes('status') ? <StatusBadge status={String(row[key] ?? '')} /> : String(row[key] ?? ''),
    }));
    return rows.length ? <DataTable columns={tableColumns} getRowKey={(row) => row.id} rows={rows} /> : <EmptyState description="No records returned by backend." title="No records" />;
}

function OptionField({ error, label, name, options }: { error?: string; label: string; name: string; options: InventoryLookupOption[] }) {
    return (
        <Field label={label}>
            <Select name={name}>
                <option value="">Select {label.toLowerCase()}</option>
                {options.map((option) => <option key={option.id} value={option.id}>{option.label}{option.secondary ? ` (${option.secondary})` : ''}</option>)}
            </Select>
            <FieldError message={error} />
        </Field>
    );
}

function TextField({ error, inputMode, label, name, type = 'text' }: { error?: string; inputMode?: 'decimal'; label: string; name: string; type?: string }) {
    return (
        <Field label={label}>
            <Input inputMode={inputMode} min={type === 'number' ? '0' : undefined} name={name} step={type === 'number' ? '0.0001' : undefined} type={type} />
            <FieldError message={error} />
        </Field>
    );
}

function TextAreaField({ error, label, name }: { error?: string; label: string; name: string }) {
    return (
        <Field label={label}>
            <Textarea name={name} />
            <FieldError message={error} />
        </Field>
    );
}

function Field({ children, label }: { children: ReactNode; label: string }) {
    return (
        <label className="space-y-2 text-sm">
            <span className="font-semibold text-slate-700">{label}</span>
            {children}
        </label>
    );
}

function GlobalError({ message }: { message: string | null }) {
    return message ? <div className="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{message}</div> : null;
}

function stringValue(form: FormData, key: string): string {
    return String(form.get(key) ?? '');
}

function captureError(error: unknown, setError: (message: string) => void, setFieldErrors: (errors: FieldErrors) => void): void {
    if (error instanceof ApiError) {
        setError(error.message);
        setFieldErrors(error.errors);
        return;
    }

    setError(errorMessage(error, 'Inventory request failed.'));
}

function errorMessage(error: unknown, fallback: string): string {
    return error instanceof Error ? error.message : fallback;
}

export function inventoryListTitle(title: string) {
    return title;
}
