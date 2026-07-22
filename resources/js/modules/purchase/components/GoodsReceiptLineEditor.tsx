import { useState, type ReactNode } from 'react';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { FormDrawer } from '@/shared/components/Drawer';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { multiplyDecimal } from '@/shared/utils/decimal';
import type { PurchaseOrderLine } from '../purchaseApi';

export interface EditableGoodsReceiptLine {
    source: PurchaseOrderLine;
    include: boolean;
    received_quantity: string;
    accepted_quantity: string;
    rejected_quantity: string;
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
    const set = <K extends keyof EditableGoodsReceiptLine>(key: K, value: EditableGoodsReceiptLine[K]) => {
        setDraft((current) => ({
            ...current,
            [key]: value,
            include: key === 'received_quantity' || key === 'accepted_quantity' || key === 'rejected_quantity'
                ? current.include || value !== '0.000000'
                : current.include,
        }));
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
            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button>
                <Button type="submit">Save line</Button>
            </div>
        </form>
    );
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
