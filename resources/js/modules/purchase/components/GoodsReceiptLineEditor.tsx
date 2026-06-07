import { useState } from 'react';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { Input } from '@/shared/components/Input';
import { Modal } from '@/shared/components/Modal';
import type { PurchaseOrderLine } from '../purchaseApi';

export interface EditableGoodsReceiptLine {
    source: PurchaseOrderLine;
    received_quantity: string;
    accepted_quantity: string;
    rejected_quantity: string;
}

type GoodsReceiptDialog = { index: number; line: EditableGoodsReceiptLine };

export function GoodsReceiptLineEditor({ lines, onChange, errorFor }: {
    lines: EditableGoodsReceiptLine[];
    onChange: (lines: EditableGoodsReceiptLine[]) => void;
    errorFor: (field: string) => string | undefined;
}) {
    const [dialog, setDialog] = useState<GoodsReceiptDialog | null>(null);
    const updateLine = (index: number, line: EditableGoodsReceiptLine) => {
        onChange(lines.map((current, currentIndex) => currentIndex === index ? line : current));
        setDialog(null);
    };
    const columns: DataColumn<EditableGoodsReceiptLine & { rowIndex: number }>[] = [
        { key: 'item', header: 'Item', render: formatGoodsReceiptItem },
        { key: 'quantity', header: 'Qty', render: (line) => line.received_quantity, className: 'tabular-nums' },
        { key: 'uom', header: 'UOM', render: (line) => line.source.uom?.code ?? '-' },
        { key: 'price', header: 'Unit price', render: (line) => line.source.unit_price, className: 'tabular-nums' },
        { key: 'accepted', header: 'Accepted', render: (line) => line.accepted_quantity, className: 'tabular-nums' },
        { key: 'rejected', header: 'Rejected', render: (line) => line.rejected_quantity, className: 'tabular-nums' },
        { key: 'remaining', header: 'Remaining', render: remainingQuantity, className: 'tabular-nums' },
        { key: 'actions', header: 'Actions', className: 'text-right', render: (line) => <button type="button" className="font-semibold text-sky-700" onClick={() => setDialog({ index: line.rowIndex, line })}>Edit line</button> },
    ];
    const rows = lines.map((line, index) => ({ ...line, rowIndex: index }));

    return (
        <>
            <DataTable rows={rows} columns={columns} rowKey={(line) => line.source.id ?? line.rowIndex} emptyMessage="Select a purchase order with receivable lines." />
            <Modal open={Boolean(dialog)} title="Edit receipt line" onClose={() => setDialog(null)}>
                {dialog && (
                    <GoodsReceiptLineForm
                        key={dialog.line.source.id}
                        line={dialog.line}
                        errorFor={(field) => errorFor(`lines.${dialog.index}.${field}`)}
                        onCancel={() => setDialog(null)}
                        onSave={(line) => updateLine(dialog.index, line)}
                    />
                )}
            </Modal>
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
    const set = <K extends keyof EditableGoodsReceiptLine>(key: K, value: EditableGoodsReceiptLine[K]) => setDraft((current) => ({ ...current, [key]: value }));

    return (
        <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); onSave(draft); }}>
            <section className="space-y-4">
                <div>
                    <h3 className="font-semibold text-slate-900">Basic Details</h3>
                    <p className="text-sm text-slate-500">{formatGoodsReceiptItem(draft)} / {draft.source.uom?.code ?? '-'}</p>
                </div>
                <div className="grid gap-4 sm:grid-cols-3">
                    <Input label="Received quantity" type="number" min="0" step="0.000001" value={draft.received_quantity} error={errorFor('received_quantity')} onChange={(event) => set('received_quantity', event.target.value)} />
                    <Input label="Accepted quantity" type="number" min="0" step="0.000001" value={draft.accepted_quantity} error={errorFor('accepted_quantity')} onChange={(event) => set('accepted_quantity', event.target.value)} />
                    <Input label="Rejected quantity" type="number" min="0" step="0.000001" value={draft.rejected_quantity} error={errorFor('rejected_quantity')} onChange={(event) => set('rejected_quantity', event.target.value)} />
                </div>
            </section>
            <div className="rounded-lg border border-slate-200 p-4 text-sm">
                <h3 className="font-semibold text-slate-900">Source Line</h3>
                <div className="mt-2 grid gap-2 sm:grid-cols-3">
                    <Summary label="Remaining" value={remainingQuantity(draft)} />
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

function Summary({ label, value }: { label: string; value: string }) {
    return <div><span className="text-xs uppercase text-slate-500">{label}</span><strong className="block tabular-nums text-slate-900">{value}</strong></div>;
}
