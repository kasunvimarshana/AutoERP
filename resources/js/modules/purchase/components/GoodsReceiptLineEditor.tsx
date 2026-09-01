import { useCallback, useState, type ReactNode } from 'react';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { FormDrawer } from '@/shared/components/Drawer';
import { Input } from '@/shared/components/Input';
import { LookupSelect } from '@/shared/components/LookupSelect';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { searchInventoryBatches } from '@/modules/inventory/inventoryApi';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { compareDecimalStrings, isPositiveDecimal, multiplyDecimal, sumDecimals } from '@/shared/utils/decimal';
import type { PurchaseOrderLine } from '../purchaseApi';

export interface EditableGoodsReceiptLine {
    source: PurchaseOrderLine;
    include: boolean;
    received_quantity: string;
    accepted_quantity: string;
    rejected_quantity: string;
    batch_allocations: EditableGoodsReceiptBatchAllocation[];
}

export interface EditableGoodsReceiptBatchAllocation {
    batch: NamedResource | null;
    batch_number: string;
    lot_number: string;
    manufacture_date: string;
    expiry_date: string;
    quantity: string;
}

type GoodsReceiptDialog = { index: number; line: EditableGoodsReceiptLine };

export function GoodsReceiptLineEditor({ lines, currencyCode, onChange, errorFor }: {
    lines: EditableGoodsReceiptLine[];
    currencyCode?: string;
    onChange: (lines: EditableGoodsReceiptLine[]) => void;
    errorFor: (field: string) => string | undefined;
}) {
    const [dialog, setDialog] = useState<GoodsReceiptDialog | null>(null);
    const updateLine = (index: number, line: EditableGoodsReceiptLine) => {
        onChange(lines.map((current, currentIndex) => currentIndex === index ? line : current));
        setDialog(null);
    };
    const toggleInclude = (index: number, include: boolean) => {
        onChange(lines.map((line, currentIndex) => currentIndex === index ? { ...line, include } : line));
    };
    const selectAll = () => onChange(lines.map((line) => ({ ...line, include: true })));
    const receiveAll = () => onChange(lines.map((line) => {
        const remaining = remainingQuantity(line);
        return { ...line, include: true, received_quantity: remaining, accepted_quantity: remaining, rejected_quantity: '0.000000' };
    }));
    const clearQuantities = () => onChange(lines.map((line) => ({ ...line, include: false, received_quantity: '0.000000', accepted_quantity: '0.000000', rejected_quantity: '0.000000' })));
    const columns: DataColumn<EditableGoodsReceiptLine & { rowIndex: number }>[] = [
        { key: 'include', header: 'Include', render: (line) => <input type="checkbox" className="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" checked={line.include} onChange={(event) => toggleInclude(line.rowIndex, event.target.checked)} /> },
        { key: 'item', header: 'Item', render: formatGoodsReceiptItem },
        { key: 'quantity', header: 'Quantity now', render: (line) => line.received_quantity, className: 'tabular-nums' },
        { key: 'uom', header: 'UOM', render: (line) => line.source.uom?.code ?? '-' },
        { key: 'price', header: 'Unit price', render: (line) => line.source.unit_price, className: 'tabular-nums' },
        { key: 'accepted', header: 'Accepted', render: (line) => line.accepted_quantity, className: 'tabular-nums' },
        { key: 'rejected', header: 'Rejected', render: (line) => line.rejected_quantity, className: 'tabular-nums' },
        { key: 'remaining', header: 'Remaining quantity', render: remainingQuantity, className: 'tabular-nums' },
        { key: 'accepted_amount', header: 'Accepted amount', render: (line) => <strong className="tabular-nums font-semibold text-slate-900"><MoneyDisplay value={acceptedAmount(line)} currency={currencyCode} /></strong>, className: 'tabular-nums' },
        { key: 'actions', header: 'Actions', className: 'text-right', render: (line) => <button type="button" className="font-semibold text-sky-700" onClick={() => setDialog({ index: line.rowIndex, line })}>Edit line</button> },
    ];
    const rows = lines.map((line, index) => ({ ...line, rowIndex: index }));

    return (
        <>
            <div className="mb-3 flex flex-wrap gap-2">
                <Button type="button" variant="secondary" onClick={selectAll}>Select All</Button>
                <Button type="button" variant="ghost" onClick={clearQuantities}>Clear</Button>
                <Button type="button" variant="secondary" onClick={receiveAll}>Receive All Remaining</Button>
            </div>
            <DataTable rows={rows} columns={columns} rowKey={(line) => line.source.id ?? line.rowIndex} emptyMessage="Select a purchase order with receivable lines." mobileSummary={formatGoodsReceiptItem} mobileDetails={(line) => <GoodsReceiptMobileDetails line={line} currencyCode={currencyCode} />} mobileActions={(line) => <button type="button" className="font-semibold text-sky-700" onClick={() => setDialog({ index: line.rowIndex, line })}>Edit line</button>} />
            <FormDrawer open={Boolean(dialog)} title="Edit receipt line" onClose={() => setDialog(null)}>
                {dialog && (
                    <GoodsReceiptLineForm
                        key={dialog.line.source.id}
                        line={dialog.line}
                        errorFor={(field) => errorFor(`lines.${dialog.index}.${field}`)}
                        onCancel={() => setDialog(null)}
                        onSave={(line) => updateLine(dialog.index, line)}
                    />
                )}
            </FormDrawer>
        </>
    );
}

function GoodsReceiptLineForm({ line, errorFor, onSave, onCancel }: {
    line: EditableGoodsReceiptLine;
    errorFor: (field: string) => string | undefined;
    onSave: (line: EditableGoodsReceiptLine) => void;
    onCancel: () => void;
}) {
    const [draft, setDraft] = useState(line);
    const batchIssue = batchAllocationIssue(draft);
    const set = <K extends keyof EditableGoodsReceiptLine>(key: K, value: EditableGoodsReceiptLine[K]) => {
        setDraft((current) => {
            const next = { ...current, [key]: value };

            if (key === 'accepted_quantity' && isBatchTracked(current) && current.batch_allocations.length === 1) {
                next.batch_allocations = current.batch_allocations.map((allocation) => ({ ...allocation, quantity: String(value) }));
            }
            if (key === 'received_quantity' || key === 'accepted_quantity' || key === 'rejected_quantity') {
                next.include = current.include || value !== '0.000000';
            }

            return next;
        });
    };

    return (
        <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); onSave(draft); }}>
            <section className="space-y-4">
                <div>
                    <h3 className="font-semibold text-slate-900">Basic Details</h3>
                    <p className="text-sm text-slate-500">{formatGoodsReceiptItem(draft)} / {draft.source.uom?.code ?? '-'}</p>
                </div>
                <div className="grid gap-4 sm:grid-cols-3">
                    <label className="flex items-center gap-2 text-sm font-medium text-slate-700">
                        <input type="checkbox" className="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" checked={draft.include} onChange={(event) => set('include', event.target.checked)} />
                        Include
                    </label>
                    <DecimalInput label="Received quantity" value={draft.received_quantity} error={errorFor('received_quantity')} onChange={(event) => set('received_quantity', event.target.value)} />
                    <DecimalInput label="Accepted quantity" value={draft.accepted_quantity} error={errorFor('accepted_quantity')} onChange={(event) => set('accepted_quantity', event.target.value)} />
                    <DecimalInput label="Rejected quantity" value={draft.rejected_quantity} error={errorFor('rejected_quantity')} onChange={(event) => set('rejected_quantity', event.target.value)} />
                </div>
            </section>
            <div className="rounded-lg border border-slate-200 p-4 text-sm">
                <h3 className="font-semibold text-slate-900">Source Line</h3>
                <div className="mt-2 grid gap-2 sm:grid-cols-3">
                    <Summary label="Remaining quantity" value={remainingQuantity(draft)} />
                    <Summary label="Unit price" value={draft.source.unit_price} />
                    <Summary label="Ordered" value={draft.source.ordered_quantity} />
                </div>
            </div>
            {isBatchTracked(draft) && <section className="space-y-3 rounded-lg border border-sky-200 bg-sky-50/40 p-4">
                <div className="flex items-center justify-between gap-3">
                    <div>
                        <h3 className="font-semibold text-slate-900">Batch / lot allocation</h3>
                        <p className="text-sm text-slate-600">Allocate the full accepted quantity across one or more batches.</p>
                    </div>
                    <Button type="button" variant="secondary" onClick={() => set('batch_allocations', [...draft.batch_allocations, emptyBatchAllocation(draft.accepted_quantity, draft.batch_allocations.length)])}>Add batch</Button>
                </div>
                {batchIssue && <p className="text-sm font-medium text-rose-700">{batchIssue}</p>}
                {draft.batch_allocations.map((allocation, index) => <BatchAllocationFields
                    key={index}
                    itemId={draft.source.item?.id ?? draft.source.item_id ?? 0}
                    itemVariantId={draft.source.item_variant?.id ?? draft.source.item_variant_id ?? undefined}
                    value={allocation}
                    errorFor={(field) => errorFor(`batch_allocations.${index}.${field}`)}
                    onChange={(next) => set('batch_allocations', draft.batch_allocations.map((current, currentIndex) => currentIndex === index ? next : current))}
                    onRemove={() => set('batch_allocations', draft.batch_allocations.filter((_, currentIndex) => currentIndex !== index))}
                />)}
            </section>}
            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button>
                <Button type="submit" disabled={batchIssue !== null}>Save line</Button>
            </div>
        </form>
    );
}

function BatchAllocationFields({ itemId, itemVariantId, value, errorFor, onChange, onRemove }: {
    itemId: number;
    itemVariantId?: number;
    value: EditableGoodsReceiptBatchAllocation;
    errorFor: (field: string) => string | undefined;
    onChange: (value: EditableGoodsReceiptBatchAllocation) => void;
    onRemove: () => void;
}) {
    const search = useCallback((params: LookupLoadParams) => searchInventoryBatches(params, { itemId, itemVariantId }), [itemId, itemVariantId]);

    return <div className="space-y-3 rounded-lg border border-slate-200 bg-white p-3">
        <div className="grid gap-3 md:grid-cols-3">
            <LookupSelect label="Existing batch" value={value.batch} onChange={(batch) => onChange({ ...value, batch })} search={search} placeholder="Select or create below" error={errorFor('batch_id')} loadOnOpen minSearchLength={0} />
            <Input label="New batch / tracking number" value={value.batch_number} disabled={Boolean(value.batch)} onChange={(event) => onChange({ ...value, batch_number: event.target.value })} error={errorFor('batch_number')} />
            <Input label="Supplier lot number" value={value.lot_number} disabled={Boolean(value.batch)} onChange={(event) => onChange({ ...value, lot_number: event.target.value })} error={errorFor('lot_number')} />
            <Input label="Manufacture date" type="date" value={value.manufacture_date} disabled={Boolean(value.batch)} onChange={(event) => onChange({ ...value, manufacture_date: event.target.value })} error={errorFor('manufacture_date')} />
            <Input label="Expiry date" type="date" value={value.expiry_date} disabled={Boolean(value.batch)} onChange={(event) => onChange({ ...value, expiry_date: event.target.value })} error={errorFor('expiry_date')} />
            <DecimalInput label="Allocated quantity" value={value.quantity} onChange={(event) => onChange({ ...value, quantity: event.target.value })} error={errorFor('quantity')} />
        </div>
        <div className="flex justify-end"><Button type="button" variant="ghost" onClick={onRemove}>Remove batch</Button></div>
    </div>;
}

function emptyBatchAllocation(acceptedQuantity: string, existingCount: number): EditableGoodsReceiptBatchAllocation {
    return {
        batch: null,
        batch_number: '',
        lot_number: '',
        manufacture_date: '',
        expiry_date: '',
        quantity: existingCount === 0 ? acceptedQuantity : '0.000000',
    };
}

export function isBatchTracked(line: EditableGoodsReceiptLine): boolean {
    return ['batch', 'lot'].includes(line.source.item?.tracking_type ?? 'none');
}

export function batchAllocationIssue(line: EditableGoodsReceiptLine): string | null {
    if (!isBatchTracked(line) || !isPositiveDecimal(line.accepted_quantity)) return null;
    if (line.batch_allocations.length === 0) {
        return 'Add a batch or lot allocation for the accepted quantity.';
    }
    if (line.batch_allocations.some((allocation) => !allocation.batch && !allocation.batch_number.trim())) {
        return 'Select an existing batch or enter a new batch number for every allocation.';
    }
    if (line.batch_allocations.some((allocation) => !isPositiveDecimal(allocation.quantity))) {
        return 'Every batch allocation requires a quantity greater than zero.';
    }

    const allocatedQuantity = sumDecimals(line.batch_allocations.map((allocation) => allocation.quantity));
    if (compareDecimalStrings(allocatedQuantity, line.accepted_quantity) !== 0) {
        return `Allocated quantity ${allocatedQuantity} must equal accepted quantity ${line.accepted_quantity}.`;
    }

    return null;
}

function formatGoodsReceiptItem(line: EditableGoodsReceiptLine): string {
    return line.source.item?.name ?? '-';
}

function remainingQuantity(line: EditableGoodsReceiptLine): string {
    return line.source.remaining_receivable_quantity ?? line.source.remaining_quantity ?? '0.000000';
}

function acceptedAmount(line: EditableGoodsReceiptLine): string {
    return multiplyDecimal(line.accepted_quantity, line.source.unit_price);
}

function Summary({ label, value }: { label: string; value: ReactNode }) {
    return <div><span className="text-xs uppercase text-slate-500">{label}</span><strong className="block tabular-nums text-slate-900">{value}</strong></div>;
}

function GoodsReceiptMobileDetails({ line, currencyCode }: { line: EditableGoodsReceiptLine; currencyCode?: string }) {
    return <div className="grid grid-cols-2 gap-2"><Summary label="Include" value={line.include ? 'Yes' : 'No'} /><Summary label="Quantity now" value={line.received_quantity} /><Summary label="Accepted" value={line.accepted_quantity} /><Summary label="Accepted amount" value={<MoneyDisplay value={acceptedAmount(line)} currency={currencyCode} />} /><Summary label="Rejected" value={line.rejected_quantity} /><Summary label="Remaining quantity" value={remainingQuantity(line)} /></div>;
}
